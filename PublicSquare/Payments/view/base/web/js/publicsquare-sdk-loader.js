define([], function () {
  'use strict';

  const DEFAULT_SDK_URL = 'https://js.publicsquare.com/v1.10.0/';

  let sdkLoadPromise = null;
  let sdkReference = null;

  function getConfiguredSdkUrl() {
    const checkoutConfig = window.checkoutConfig;
    const paymentConfig = checkoutConfig && checkoutConfig.payment && checkoutConfig.payment.publicsquare_payments;
    const configuredUrl = paymentConfig && paymentConfig.checkoutScriptUrl;

    if (typeof configuredUrl === 'string' && configuredUrl.trim()) {
      return configuredUrl.trim();
    }

    return DEFAULT_SDK_URL;
  }

  function normalizeSdkCandidate(candidate) {
    if (candidate && typeof candidate.init === 'function') {
      return candidate;
    }

    if (candidate && candidate.default && typeof candidate.default.init === 'function') {
      return candidate.default;
    }

    return null;
  }

  function resolveSdk(capturedModule) {
    const candidates = [
      capturedModule,
      window.publicsquarejs,
      window.PublicSquare,
      window.publicsquare,
    ];

    for (let index = 0; index < candidates.length; index += 1) {
      const sdk = normalizeSdkCandidate(candidates[index]);
      if (sdk) {
        return sdk;
      }
    }

    return null;
  }

  function runWithAmdGuard(executor) {
    const hasOwnDefine = Object.prototype.hasOwnProperty.call(window, 'define');
    const hasOwnRequire = Object.prototype.hasOwnProperty.call(window, 'require');
    const originalDefine = window.define;
    const originalRequire = window.require;

    let capturedModule;
    const guardedDefine = function (name, deps, factory) {
      if (typeof name === 'function') {
        capturedModule = name();
        return;
      }

      if (Array.isArray(name) && typeof deps === 'function') {
        capturedModule = deps();
        return;
      }

      if (typeof name === 'string' && typeof deps === 'function') {
        capturedModule = deps();
        return;
      }

      if (typeof name === 'string' && Array.isArray(deps) && typeof factory === 'function') {
        capturedModule = factory();
      }
    };

    const restore = function () {
      if (hasOwnDefine) {
        window.define = originalDefine;
      } else {
        delete window.define;
      }

      if (hasOwnRequire) {
        window.require = originalRequire;
      } else {
        delete window.require;
      }
    };

    window.define = guardedDefine;
    window.require = undefined;

    try {
      const result = executor(function () {
        return capturedModule;
      });

      if (result && typeof result.then === 'function') {
        return result.finally(restore);
      }

      restore();
      return result;
    } catch (error) {
      restore();
      throw error;
    }
  }

  function loadSdkScript(sdkUrl) {
    return runWithAmdGuard(function (getCapturedModule) {
      return new Promise(function (resolve, reject) {
        const existingScript = Array.from(document.querySelectorAll('script'))
          .find(function (script) {
            return script.src === sdkUrl;
          });

        if (existingScript) {
          const existingSdk = resolveSdk(getCapturedModule());
          if (existingSdk) {
            resolve(existingSdk);
            return;
          }

          if (existingScript.readyState && existingScript.readyState !== 'loading') {
            reject(new Error('PublicSquare SDK script exists, but no compatible export is available.'));
            return;
          }
        }

        const scriptTag = existingScript || document.createElement('script');

        const onLoad = function () {
          const resolvedSdk = resolveSdk(getCapturedModule());
          if (resolvedSdk) {
            resolve(resolvedSdk);
            return;
          }

          reject(new Error('PublicSquare SDK loaded, but no compatible global/module export was found.'));
        };

        const onError = function () {
          reject(new Error('Unable to load PublicSquare SDK script.'));
        };

        scriptTag.addEventListener('load', onLoad, { once: true });
        scriptTag.addEventListener('error', onError, { once: true });

        if (!existingScript) {
          scriptTag.src = sdkUrl;
          scriptTag.async = true;
          (document.head || document.body).appendChild(scriptTag);
        }
      });
    });
  }

  function ensureSdkLoaded() {
    if (sdkReference) {
      return Promise.resolve(sdkReference);
    }

    if (sdkLoadPromise) {
      return sdkLoadPromise;
    }

    const sdkUrl = getConfiguredSdkUrl();
    sdkLoadPromise = loadSdkScript(sdkUrl).then(function (sdk) {
      sdkReference = sdk;
      return sdk;
    });

    return sdkLoadPromise;
  }

  const loader = {
    init: function (apiKey, options) {
      return ensureSdkLoaded().then(function (sdk) {
        return runWithAmdGuard(function () {
          return Promise.resolve(sdk.init(apiKey, options)).then(function (initializedSdk) {
            sdkReference = initializedSdk || sdk;
            return initializedSdk;
          });
        });
      });
    },
  };

  Object.defineProperty(loader, 'cards', {
    enumerable: true,
    configurable: false,
    get: function () {
      return sdkReference && sdkReference.cards;
    },
  });

  return loader;
});
