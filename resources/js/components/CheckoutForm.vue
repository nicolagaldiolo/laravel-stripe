<template>
  <div class="flex justify-center">
      <div class="w-full inline-block border p-4 rounded-md">
        <div class="w-full">
          <form id="payment-form" @submit.prevent="subscribe">


              <div class="form-group">

                <label>Plan</label>

                <select class="form-control" v-model="priceId">
                  <option v-for="plan in plans" v-bind:value="plan.stripe_id">{{ plan.name }}</option>
                </select>
              </div>

              <div class="form-group">
                <label>Card</label>
                <div class="appearance-none block w-full bg-gray-200 text-gray-700 border rounded-md py-3 px-2 leading-tight focus:outline-none focus:bg-white" id="card-element"></div>
                <div id="card-element-errors" class="text-gray-700 text-base mt-2" role="alert"></div>
              </div>

              <div class="form-group">
                <label>Coupon Code</label>
                <input type="text" class="form-control" v-model="coupon">
              </div>
              <div class="form-group">
                <button id="submit-premium" class="w-full bg-pasha hover:bg-white hover:shadow-outline hover:text-pasha hover:border hover:border-black focus:shadow-outline text-white focus:bg-white focus:text-pasha font-light py-2 px-4 rounded-md" type="submit">
                  <div id="loading" class="hidden">Subscribing...</div>
                  <span id="button-text" class="">Subscribe</span>
                </button>
              </div>
          </form>
        </div>
      </div>
    </div>
</template>

<script>
    export default {

      props: ['plans'],

      data(){
        return {
          priceId: this.plans[0].stripe_id,
          card: null,
          customer: window.App.user.customer_id,
          // A reference to Stripe.js initialized with your real test publishable API key.
          stripe: Stripe(window.App.stripe_key),
          coupon: 'FIRST-COUPON',
        }
      },

      methods: {

        subscribe(){
          // If a previous payment was attempted, get the lastest invoice
          const latestInvoicePaymentIntentStatus = localStorage.getItem(
              'latestInvoicePaymentIntentStatus'
          );

          if (latestInvoicePaymentIntentStatus === 'requires_payment_method') {
            const invoiceId = localStorage.getItem('latestInvoiceId');
            const isPaymentRetry = true;
            // create new payment method & retry payment on invoice with new payment method
            this.createPaymentMethod({
              isPaymentRetry,
              invoiceId,
            });
          } else {
            // create new payment method & create subscription
            this.createPaymentMethod({});
          }
        },

        stripeElements(publishableKey) {
          if (document.getElementById('card-element')) {
            let elements = this.stripe.elements();

            // Card Element styles
            let style = {
              base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily:
                    '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif',
                fontSmoothing: 'antialiased',
                '::placeholder': {
                  color: '#a0aec0',
                },
              },
            };

            this.card = elements.create('card', { style: style });

            this.card.mount('#card-element');

            this.card.on('focus', function () {
              let el = document.getElementById('card-element-errors');
              el.classList.add('focused');
            });

            this.card.on('blur', function () {
              let el = document.getElementById('card-element-errors');
              el.classList.remove('focused');
            });

            this.card.on('change', (event) => {
              this.displayError(event);
            });
          }
        },

        displayError(event) {

          console.log(event);

          //changeLoadingStatePrices(false);
          let displayError = document.getElementById('card-element-errors');
          if (event.error) {
            alert(event.error.message);
            displayError.textContent = event.error.message;
          } else {
            displayError.textContent = '';
          }
        },

        createPaymentMethod({ isPaymentRetry, invoiceId }) {
          const customerId = this.customer;
          // Set up payment method for recurring usage
          let billingName = window.App.user.name;
          let priceId = this.priceId;

          this.stripe
              .createPaymentMethod({
                type: 'card',
                card: this.card,
                billing_details: {
                  name: billingName,
                },
              })
              .then((result) => {
                if (result.error) {
                  this.displayError(result);
                } else {
                  if (isPaymentRetry) {
                    // Update the payment method and retry invoice payment
                    this.retryInvoiceWithNewPaymentMethod({
                      customerId: customerId,
                      paymentMethodId: result.paymentMethod.id,
                      invoiceId: invoiceId,
                      priceId: priceId,
                    });
                  } else {
                    // Create the subscription

                    //console.log(customerId, result.paymentMethod.id, priceId);

                    this.createSubscription({
                      customerId: customerId,
                      paymentMethodId: result.paymentMethod.id,
                      priceId: priceId,
                    });
                  }
                }
              });
        },

        createCustomer() {
          return fetch('/create-customer', {
            method: 'post',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.App.csrf_token
            },
            body: JSON.stringify({
              email: window.App.user.email,
            }),
          })
              .then((response) => {
                return response.json();
              })
              .then((result) => {
                return result;
              });
        },

        handlePaymentThatRequiresCustomerAction({
                                                           subscription,
                                                           invoice,
                                                           priceId,
                                                           paymentMethodId,
                                                           isRetry,
                                                         }) {
          if (subscription && subscription.status === 'active') {
            // subscription is active, no customer actions required.
            return { subscription, priceId, paymentMethodId };
          }

          // If it's a first payment attempt, the payment intent is on the subscription latest invoice.
          // If it's a retry, the payment intent will be on the invoice itself.
          let paymentIntent = invoice
              ? invoice.payment_intent
              : subscription.latest_invoice.payment_intent;

          if (
              paymentIntent.status === 'requires_action' ||
              (isRetry === true && paymentIntent.status === 'requires_payment_method')
          ) {
            return this.stripe
                .confirmCardPayment(paymentIntent.client_secret, {
                  payment_method: paymentMethodId,
                })
                .then((result) => {
                  if (result.error) {
                    // start code flow to handle updating the payment details
                    // Display error message in your UI.
                    // The card was declined (i.e. insufficient funds, card has expired, etc)
                    throw result;
                  } else {
                    if (result.paymentIntent.status === 'succeeded') {
                      // There's a risk of the customer closing the window before callback
                      // execution. To handle this case, set up a webhook endpoint and
                      // listen to invoice.paid. This webhook endpoint returns an Invoice.
                      return {
                        priceId: priceId,
                        subscription: subscription,
                        invoice: invoice,
                        paymentMethodId: paymentMethodId,
                      };
                    }
                  }
                });
          } else {
            // No customer action needed
            return { subscription, priceId, paymentMethodId };
          }
        },

        handleRequiresPaymentMethod({
                                               subscription,
                                               paymentMethodId,
                                               priceId,
                                             }) {
          if (subscription.status === 'active') {
            // subscription is active, no customer actions required.
            return { subscription, priceId, paymentMethodId };
          } else if (
              subscription.latest_invoice.payment_intent.status ===
              'requires_payment_method'
          ) {
            // Using localStorage to store the state of the retry here
            // (feel free to replace with what you prefer)
            // Store the latest invoice ID and status
            localStorage.setItem('latestInvoiceId', subscription.latest_invoice.id);
            localStorage.setItem(
                'latestInvoicePaymentIntentStatus',
                subscription.latest_invoice.payment_intent.status
            );
            throw { error: { message: 'Your card was declined.' } };
          } else {
            return { subscription, priceId, paymentMethodId };
          }
        },

        onSubscriptionComplete(result) {
          alert('Apposto');
          // Payment was successful. Provision access to your service.
          // Remove invoice from localstorage because payment is now complete.
          this.clearCache();
          // Change your UI to show a success message to your customer.
          // Call your backend to grant access to your service based on
          // the product your customer subscribed to.
          // Get the product by using result.subscription.price.product
        },

        createSubscription({ customerId, paymentMethodId, priceId }) {

          return (
              fetch('/create-subscription', {
                method: 'post',
                headers: {
                  'Content-type': 'application/json',
                  'X-CSRF-TOKEN': window.App.csrf_token
                },
                body: JSON.stringify({
                  paymentMethodId: paymentMethodId,
                  coupon: this.coupon,
                  priceId: priceId,
                }),
              })
                  .then((response) => {
                    return response.json();
                  })
                  // If the card is declined, display an error to the user.
                  .then((result) => {
                    if (result.error) {
                      // The card had an error when trying to attach it to a customer
                      throw result;
                    }
                    return result;
                  })
                  // Normalize the result to contain the object returned
                  // by Stripe. Add the addional details we need.
                  .then((result) => {
                    return {
                      // Use the Stripe 'object' property on the
                      // returned result to understand what object is returned.
                      subscription: result,
                      paymentMethodId: paymentMethodId,
                      priceId: priceId,
                    };
                  })
                  // Some payment methods require a customer to do additional
                  // authentication with their financial institution.
                  // Eg: 2FA for cards.
                  .then(this.handlePaymentThatRequiresCustomerAction)
                  // If attaching this card to a Customer object succeeds,
                  // but attempts to charge the customer fail. You will
                  // get a requires_payment_method error.
                  .then(this.handleRequiresPaymentMethod)
                  // No more actions required. Provision your service for the user.
                  .then(this.onSubscriptionComplete)
                  .catch((error) => {
                    debugger;
                    console.log('Dovrei entrare qui');
                    // An error has happened. Display the failure to the user here.
                    // We utilize the HTML element we created.
                    this.displayError(error);
                  })
          );
        },

        retryInvoiceWithNewPaymentMethod({
                                                    customerId,
                                                    paymentMethodId,
                                                    invoiceId,
                                                    priceId,
                                                  }) {
          return (
              fetch('/retry-invoice', {
                method: 'post',
                headers: {
                  'Content-type': 'application/json',
                  'X-CSRF-TOKEN': window.App.csrf_token
                },
                body: JSON.stringify({
                  customerId: customerId,
                  paymentMethodId: paymentMethodId,
                  invoiceId: invoiceId,
                }),
              })
                  .then((response) => {
                    return response.json();
                  })
                  // If the card is declined, display an error to the user.
                  .then((result) => {
                    if (result.error) {
                      // The card had an error when trying to attach it to a customer
                      throw result;
                    }
                    return result;
                  })
                  // Normalize the result to contain the object returned
                  // by Stripe. Add the addional details we need.
                  .then((result) => {
                    return {
                      // Use the Stripe 'object' property on the
                      // returned result to understand what object is returned.
                      invoice: result,
                      paymentMethodId: paymentMethodId,
                      priceId: priceId,
                      isRetry: true,
                    };
                  })
                  // Some payment methods require a customer to be on session
                  // to complete the payment process. Check the status of the
                  // payment intent to handle these actions.
                  .then(this.handlePaymentThatRequiresCustomerAction)
                  // No more actions required. Provision your service for the user.
                  .then(this.onSubscriptionComplete)
                  .catch((error) => {
                    // An error has happened. Display the failure to the user here.
                    // We utilize the HTML element we created.
                    this.displayError(error);
                  })
          );
        },

        retrieveUpcomingInvoice(customerId, subscriptionId, newPriceId) {
          return fetch('/retrieve-upcoming-invoice', {
            method: 'post',
            headers: {
              'Content-type': 'application/json',
              'X-CSRF-TOKEN': window.App.csrf_token
            },
            body: JSON.stringify({
              customerId: customerId,
              subscriptionId: subscriptionId,
              newPriceId: newPriceId,
            }),
          })
              .then((response) => {
                return response.json();
              })
              .then((invoice) => {
                return invoice;
              });
        },

        cancelSubscription() {
          //changeLoadingStatePrices(true);
          const params = new URLSearchParams(document.location.search.substring(1));
          const subscriptionId = params.get('subscriptionId');

          return fetch('/cancel-subscription', {
            method: 'post',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.App.csrf_token
            },
            body: JSON.stringify({
              subscriptionId: subscriptionId,
            }),
          })
              .then((response) => {
                return response.json();
              })
              .then((cancelSubscriptionResponse) => {
                return this.subscriptionCancelled(cancelSubscriptionResponse);
              });
        },

        updateSubscription(priceId, subscriptionId) {
          return fetch('/update-subscription', {
            method: 'post',
            headers: {
              'Content-type': 'application/json',
              'X-CSRF-TOKEN': window.App.csrf_token
            },
            body: JSON.stringify({
              subscriptionId: subscriptionId,
              newPriceId: priceId,
            }),
          })
              .then((response) => {
                return response.json();
              })
              .then((response) => {
                return response;
              });
        },

        // Shows the cancellation response
        subscriptionCancelled() {
          document.querySelector('#subscription-cancelled').classList.remove('hidden');
          document.querySelector('#subscription-settings').classList.add('hidden');
        },

        clearCache() {
          localStorage.clear();
        },

      },

      mounted() {

        console.log(this.customer);

        this.stripeElements(window.App.stripe_key);

        if(!window.App.user.customer_id){
          this.createCustomer().then(result => {
            this.customer = result.customer.id;

            console.log(this.customer);

          });
        };

      }
}
</script>

<style scoped>
.result-message {
  line-height: 22px;
  font-size: 16px;
}

.result-message a {
  color: rgb(89, 111, 214);
  font-weight: 600;
  text-decoration: none;
}

.hidden {
  display: none;
}

#card-error {
  color: rgb(105, 115, 134);
  text-align: left;
  font-size: 13px;
  line-height: 17px;
  margin-top: 12px;
}

#card-element {
  border-radius: 4px 4px 0 0 ;
  padding: 12px;
  border: 1px solid rgba(50, 50, 93, 0.1);
  height: 44px;
  width: 100%;
  background: white;
}

#payment-request-button {
  margin-bottom: 32px;
}

/* Buttons and links */
button {
  background: #5469d4;
  color: #ffffff;
  font-family: Arial, sans-serif;
  border-radius: 0 0 4px 4px;
  border: 0;
  padding: 12px 16px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: block;
  transition: all 0.2s ease;
  box-shadow: 0px 4px 5.5px 0px rgba(0, 0, 0, 0.07);
  width: 100%;
}
button:hover {
  filter: contrast(115%);
}
button:disabled {
  opacity: 0.5;
  cursor: default;
}

/* spinner/processing state, errors */
.spinner,
.spinner:before,
.spinner:after {
  border-radius: 50%;
}
.spinner {
  color: #ffffff;
  font-size: 22px;
  text-indent: -99999px;
  margin: 0px auto;
  position: relative;
  width: 20px;
  height: 20px;
  box-shadow: inset 0 0 0 2px;
  -webkit-transform: translateZ(0);
  -ms-transform: translateZ(0);
  transform: translateZ(0);
}
.spinner:before,
.spinner:after {
  position: absolute;
  content: "";
}
.spinner:before {
  width: 10.4px;
  height: 20.4px;
  background: #5469d4;
  border-radius: 20.4px 0 0 20.4px;
  top: -0.2px;
  left: -0.2px;
  -webkit-transform-origin: 10.4px 10.2px;
  transform-origin: 10.4px 10.2px;
  -webkit-animation: loading 2s infinite ease 1.5s;
  animation: loading 2s infinite ease 1.5s;
}
.spinner:after {
  width: 10.4px;
  height: 10.2px;
  background: #5469d4;
  border-radius: 0 10.2px 10.2px 0;
  top: -0.1px;
  left: 10.2px;
  -webkit-transform-origin: 0px 10.2px;
  transform-origin: 0px 10.2px;
  -webkit-animation: loading 2s infinite ease;
  animation: loading 2s infinite ease;
}

@-webkit-keyframes loading {
  0% {
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@keyframes loading {
  0% {
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
</style>