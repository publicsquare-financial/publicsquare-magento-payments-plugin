define([], function () {
  'use strict';

  var SDK_URL = 'https://js.publicsquare.com/v1.10.0/';
  var DEFAULT_LOAD_TIMEOUT_MS = 15000;

  var sdkLoadPromise = null;
  var sdkReference = null;

  function getLoadTimeoutMs() {
    var override = window.publicsquareSdkLoadTimeoutMs;
    if (typeof override === 'number' && override > 0) {
      return override;
    }
    return DEFAULT_LOAD_TIMEOUT_MS;
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

  function resolveSdk(loadedModule) {
    var candidates = [
      loadedModule,
      window.publicsquarejs,
      window.PublicSquare
    ];

    for (var i = 0; i < candidates.length; i += 1) {
      var sdk = normalizeSdkCandidate(candidates[i]);
      if (sdk) {
        return sdk;
      }
    }

    return null;
  }

  function getRequire() {
    if (window.require) {
      return window.require;
    }

    if (typeof require === 'function') {
      return require;
    }

    return null;
  }

  function loadSdkModule() {
    return new Promise(function (resolve, reject) {
      var loaderRequire = getRequire();
      if (!loaderRequire) {
        reject(new Error('RequireJS is not available to load the PublicSquare SDK.'));
        return;
      }

      var timeoutMs = getLoadTimeoutMs();
      var timeoutId = null;
      var settled = false;

      function cleanup() {
        if (timeoutId !== null) {
          clearTimeout(timeoutId);
          timeoutId = null;
        }
      }

      function settle(fn) {
        if (settled) {
          return;
        }
        settled = true;
        cleanup();
        fn();
      }

      function onLoaded(loadedModule) {
        settle(function () {
          var resolvedSdk = resolveSdk(loadedModule);
          if (resolvedSdk) {
            resolve(resolvedSdk);
            return;
          }
          reject(new Error('PublicSquare SDK loaded, but no compatible global/module export was found.'));
        });
      }

      function onError() {
        settle(function () {
          reject(new Error('Unable to load PublicSquare SDK script.'));
        });
      }

      timeoutId = setTimeout(function () {
        settle(function () {
          reject(new Error('PublicSquare SDK load timed out after ' + timeoutMs + 'ms.'));
        });
      }, timeoutMs);

      loaderRequire([SDK_URL], onLoaded, onError);
    });
  }

  function runWithSdkAmdGuard(executor) {
    var originalDefine = window.define;

    if (typeof originalDefine !== 'function') {
      return executor();
    }

    function guardedDefine() {
      return originalDefine.apply(this, arguments);
    }

    // Keep AMD define available for Magento modules, but hide the AMD flag so
    // SDK-injected UMD scripts attach their expected browser globals.
    window.define = guardedDefine;

    function restore() {
      if (window.define === guardedDefine) {
        window.define = originalDefine;
      }
    }

    try {
      var result = executor();

      if (result && typeof result.then === 'function') {
        return result.then(
          function (value) { restore(); return value; },
          function (error) { restore(); throw error; }
        );
      }

      restore();
      return result;
    } catch (error) {
      restore();
      throw error;
    }
  }

  function ensureSdkLoaded() {
    if (sdkReference) {
      return Promise.resolve(sdkReference);
    }

    if (sdkLoadPromise) {
      return sdkLoadPromise;
    }

    sdkLoadPromise = loadSdkModule().then(
      function (sdk) {
        sdkReference = sdk;
        return sdk;
      },
      function (error) {
        sdkLoadPromise = null;
        throw error;
      }
    );

    return sdkLoadPromise;
  }

  var loader = {
    init: function (apiKey, options) {
      return ensureSdkLoaded().then(function (sdk) {
        return runWithSdkAmdGuard(function () {
          return Promise.resolve(sdk.init(apiKey, options)).then(function (initializedSdk) {
            sdkReference = initializedSdk || sdk;
            return initializedSdk;
          });
        });
      });
    }
  };

  Object.defineProperty(loader, 'cards', {
    enumerable: true,
    configurable: true,
    get: function () {
      return sdkReference && sdkReference.cards;
    }
  });

  return loader;
});
