<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Accept a card payment</title>
    <meta name="description" content="A demo of a card payment on Stripe" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>

    <script>
        window.App = <?php echo json_encode([
            'csrf_token' => csrf_token(),
            'stripe_key' => config('services.stripe.key'),
            'user' => \Illuminate\Support\Facades\Auth::user()
        ]); ?>
    </script>

</head>

<body>
    <div id="app">
        <checkout-form :plans="{{ \App\Models\Plan::all() }}"></checkout-form>
    </div>

</body>
</html>