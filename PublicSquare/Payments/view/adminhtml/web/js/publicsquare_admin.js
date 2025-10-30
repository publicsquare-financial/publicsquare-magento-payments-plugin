define(
  [
    'jquery',
    'publicsquare_payments'
  ],
  function ($, publicsquare) {
    let config = {},
      firstNameSelector = '#order-billing_address_firstname',
      lastNameSelector = '#order-billing_address_lastname',
      elementsFormSelector = '#publicsquare-elements-form',
      paymentMethodNonceSelector = '#publicsquare_payments_payment_method_nonce',
      element = $(elementsFormSelector),
      originalOrderSubmit;

    async function onSubmit(e) {
      const $form = $('#edit_form');
      try {
        $form.trigger('processStart');
        const cardholder_name = [$(firstNameSelector).val(), $(lastNameSelector).val()].filter(Boolean).join(' ');
        if (!cardholder_name) {
          alert('Cardholder name is required');
          throw new Error('Cardholder name is required');
        } else if (!publicsquare.cardElement || (publicsquare.cardElement.metadata && publicsquare.cardElement.metadata.valid === false)) {
          alert('Payment details are invalid or not ready.');
          throw new Error('Card element invalid or missing');
        }
        // Try to tokenize; if anything goes wrong, block submission (no mock fallback)
        const card = await publicsquare.createCard(cardholder_name, publicsquare.cardElement);
        if (card && card.id) {
          $(paymentMethodNonceSelector).val(card.id);
        } else {
          alert('Unable to tokenize card.');
          throw new Error('Tokenization returned no id');
        }
        if (!$form.valid()) {
          $form.trigger('processStop');
          return
        }
        originalOrderSubmit();
      } catch {
        $form.trigger('processStop');
      }
    }

    function enableSubmitHandler() {
      if (!window.order || !window.order.submit) return;
      if (!originalOrderSubmit) {
        originalOrderSubmit = window.order.submit;
      }
      if (window.order.paymentMethod === 'publicsquare_payments') {
        window.order.submit = onSubmit;
      } else if (originalOrderSubmit) {
        window.order.submit = originalOrderSubmit;
      }
    }

    function observe() {
      const paymentMethods = document.querySelector('#edit_form');
      if (paymentMethods) {
        renderElements();
        observer.observe(paymentMethods, {
          childList: true,
          subtree: true,
          attributes: true,
        })
      }
    }

    function renderElements() {
      element = $(elementsFormSelector);
      if (element.length && !element.children().length && !publicsquare.loading) {
        observer.disconnect();
        requestAnimationFrame(() => {
          publicsquare.initElements({
            apiKey: config.pk || '',
            selector: elementsFormSelector,
            mock: !!config.mock
          }, () => {
            observe();
          })
        })
      }
      enableSubmitHandler();
    }

    const observer = new MutationObserver((mutations) => {
      if (mutations.find((cur) => $(cur.target).is($('.payment-method') || $(cur.target).has('.payment-method')))) {
        renderElements();
      }
    });

    function init(_config) {
      config = _config;
      observe();
    }

    return {
      init
    };
  });