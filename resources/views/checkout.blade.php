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
    <div class="container">

        @if(auth()->user()->isActive())
            @if(auth()->user()->isOnGracePeriod())
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Periodo di grazia</h3>
                    </div>
                    <div class="card-body">
                        <form action="/subscription" method="POST">
                            @csrf
                            @method('PATCH')
                            <label>La tua subscription scadrà il {{ \Illuminate\Support\Facades\Auth::user()->subscription_end_at->format('d-m-Y') }}</label>
                            <br>
                            <button class="btn btn-primary" type="submit">Riattiva subscription</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Cancella</h3>
                    </div>
                    <div class="card-body">
                        <form action="/subscription" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">Cancella subscription</button>
                        </form>
                    </div>
                </div>
            @endif
        @else
            <div id="app">
                <checkout-form :plans="{{ \App\Models\Plan::all() }}"></checkout-form>
            </div>
        @endif

        @if(count(auth()->user()->payments) > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Pagamenti</h3>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach(\Illuminate\Support\Facades\Auth::user()->payments as $payment)
                        <li class="list-group-item">
                            {{ $payment->created_at->diffForHumans() }}
                            <strong>${{ number_format($payment->amount / 100, 2) }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

</body>
</html>