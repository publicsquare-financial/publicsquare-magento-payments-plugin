// Copyright © PublicSquare Financial, LLC
//
// @package    PublicSquare_Payments
// @version    4.0.8
define(
  [
    'publicsquarejs'
  ],
  function (publicsquarejs) {
    'use strict';
    return (window.publicsquare = {

      // Properties
      version: "1.0.0",
      publicsquareJs: null,
      cardElement: null,
      loading: false,
      mockIframe: null,

      initElements: async function (params = {}, callback) {
        if (window.checkoutConfig && window.checkoutConfig.payment && window.checkoutConfig.payment.publicsquare_payments && window.checkoutConfig.payment.publicsquare_payments.mock) {
          // Mock: create a same-origin iframe with expected inputs
          const container = document.querySelector(params.selector)
          if (container) {
            container.innerHTML = ''
            this.mockIframe = document.createElement('iframe')
            this.mockIframe.id = 'psq-mock-iframe-' + Date.now()
            this.mockIframe.setAttribute('frameborder', '0')
            this.mockIframe.style.cssText = 'width:100%;height:120px;border:0;'
            container.appendChild(this.mockIframe)
            try {
              const doc = this.mockIframe.contentDocument || this.mockIframe.contentWindow.document
              doc.open()
              doc.write(
                '<!doctype html><html><head><meta charset="utf-8"></head>'+
                '<body style="margin:0;padding:8px;font-family:sans-serif;">'+
                '<input id="cardNumber" placeholder="Card Number" style="display:block;width:100%;margin:4px 0;padding:6px;">'+
                '<input id="expirationDate" placeholder="MM/YY" style="display:block;width:100%;margin:4px 0;padding:6px;">'+
                '<input id="cvc" placeholder="CVC" style="display:block;width:100%;margin:4px 0;padding:6px;">'+
                '</body></html>'
              )
              doc.close()
            } catch (e) {}
          }
          this.cardElement = {
            mount: () => {},
            unmount: () => { if (this.mockIframe && this.mockIframe.parentNode) this.mockIframe.parentNode.removeChild(this.mockIframe) },
            metadata: { valid: true }
          }
          this.publicsquareJs = {
            createCardElement: () => this.cardElement,
            cards: {
              create: async ({ cardholder_name, card }) => {
                // Read last4 from the mock iframe if available
                try {
                  const doc = this.mockIframe && (this.mockIframe.contentDocument || this.mockIframe.contentWindow.document)
                  const num = doc && doc.getElementById('cardNumber') ? (doc.getElementById('cardNumber').value || '4242424242424242') : '4242424242424242'
                  const digits = (num+'').replace(/\D/g,'')
                  const last4 = digits.slice(-4) || '4242'
                  return { id: 'card_mock_' + last4 }
                } catch (e) {
                  return { id: 'card_mock_4242' }
                }
              }
            }
          }
          this.loading = false
          if (typeof callback === 'function') callback(this)
          return
        }
        if (!this.publicsquareJs && !this.loading) {
          this.loading = true;
          const _publicsquare = await publicsquarejs.init(params.apiKey)
          this.publicsquareJs = _publicsquare
          if (this.cardElement) {
            this.cardElement.unmount()
          }
          this.cardElement = _publicsquare.createCardElement({})
          this.cardElement.mount(params.selector)
          this.loading = false;
        } else if (!this.loading && this.cardElement) {
          this.cardElement.unmount()
          this.cardElement = this.publicsquareJs.createCardElement({})
          this.cardElement.mount(params.selector)
        }
        if (typeof callback === 'function') {
          callback(this)
        }
      },
      /**
       * Creates a card object in PublicSquare
       * @param {string} cardHolderName
       * @param {HTMLDivElement} card - This is the card element
       */
      createCard: async function (cardholder_name, card) {
        if (window.checkoutConfig && window.checkoutConfig.payment && window.checkoutConfig.payment.publicsquare_payments && window.checkoutConfig.payment.publicsquare_payments.mock) {
          return { id: 'card_mock_4242' }
        }
        if (!this.publicsquareJs) {
          throw new Error('PublicSquare not initialized yet')
        } else {
          if (this.loading) {
            throw new Error('PublicSquare is still loading')
          }
          this.loading = true;
          const newCard = await this.publicsquareJs.cards.create({
            cardholder_name,
            card
          })
          this.loading = false;
          return newCard
        }
      }
    });
  }
);
