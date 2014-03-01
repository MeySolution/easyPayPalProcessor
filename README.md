# Handle PayPal-Payments with PHP - the easy way


This tool uses the offical [PayPal REST PHP SDK](https://github.com/paypal/rest-api-sdk-php).

The meyPayment class trys to make the payment process as easy as possible.

You just have to set Client ID and Client Secret.
After that you can add as many items as you want by just entering a item name and the price.

After redirecting the buyer to paypal you can fullfill the payment by just entering the paypal-buyer-id (send by the REST API after the buyer ends the payment process).

## Planned features
- Adding Taxes
- Adding Shipping costs 