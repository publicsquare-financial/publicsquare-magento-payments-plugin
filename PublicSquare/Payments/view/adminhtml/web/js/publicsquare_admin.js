define(
  [
    'jquery',
    'publicsquare_payments',
    // 'publicsquare_admin',
  ],
  function ($, publicsquare) {
    let config = {},
      upperSubmitButton = $('#submit_order_top_button'),
      lowerSubmitButtonSelector = '.action-default.scalable.save.primary',
      firstNameSelector = '#order-billing_address_firstname',
      lastNameSelector = '#order-billing_address_lastname',
      elementsFormSelector = '#publicsquare-elements-form',
      paymentMethodNonceSelector = '#publicsquare_payments_payment_method_nonce',
      paymentsFormSelector = '#payment_form_publicsquare_payments',
      element = $(elementsFormSelector),
      lowerOriginalOnClick;

    async function onSubmit(e) {
      e.preventDefault();
      e.stopPropagation();
      try {
        const cardholder_name = [$(firstNameSelector).val(), $(lastNameSelector).val()].filter(Boolean).join(' ');
        if (!cardholder_name) {
          return alert('Cardholder name is required');
        } else if (!publicsquare.cardElement || !publicsquare.cardElement.metadata.valid) {
          return alert('The card is invalid. Please check the card details and try again.');
        }
        const card = await publicsquare.createCard(cardholder_name, publicsquare.cardElement);
        $(paymentMethodNonceSelector).val(card.id);
        lowerOriginalOnClick(e);
      } catch (e) {
        console.error(e);
        return false;
      }
    }

    function enableSubmitHandler() {
      const lowerSubmitButton = $(lowerSubmitButtonSelector);
      if (lowerSubmitButton[0].onclick !== onSubmit) {
        lowerOriginalOnClick = lowerSubmitButton[0].onclick;
        upperSubmitButton[0].onclick = onSubmit;
        lowerSubmitButton[0].onclick = onSubmit;
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
            selector: elementsFormSelector
          }, () => {
            observe();
          })
        })
      }
      enableSubmitHandler();
    }

    const observer = new MutationObserver((mutations) => {
      if (mutations.find((cur) => $(cur.target).find(paymentsFormSelector).length)) {
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