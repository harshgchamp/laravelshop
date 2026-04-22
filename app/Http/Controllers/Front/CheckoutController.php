<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\UserAddress;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $cartItems = CartItem::with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return back()->with('error', 'Cart is empty');
        }

        $newAddress = $request->address;
        if ($newAddress['address1'] != null) {
            $address = UserAddress::where('is_main', 1)->count();
            if ($address > 0) {
                $address = UserAddress::where('is_main', 1)->update(['is_main' => 0]);
            }
            $address = new UserAddress;
            $address->address1 = $newAddress['address1'];
            $address->state = $newAddress['state'];
            $address->postcode = $newAddress['postcode'];
            $address->city = $newAddress['city'];
            $address->country_code = $newAddress['country_code'];
            $address->type = $newAddress['type'];
            $address->user_id = $user->id;
            $address->save();
        }

        $mainAddress = $user->user_address()->where('is_main', 1)->first();

        DB::beginTransaction();

        try {

            $total = $cartItems->sum(function ($item) {
                return ($item->product->discount_price ?? $item->product->price) * $item->quantity;
            });

            $order = Order::create([
                'status' => 'pending',
                'total_price' => $total,
                'user_id' => $user->id,
                'user_address_id' => $mainAddress->id,
            ]);

            foreach ($cartItems as $item) {

                $price = $item->product->discount_price ?? $item->product->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $price,
                ]);
            }

            $lineItems = [];

            foreach ($cartItems as $item) {

                $price = $item->product->discount_price ?? $item->product->price;

                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $item->product->title,
                        ],
                        'unit_amount' => (int) ($price * 100),
                    ],
                    'quantity' => $item->quantity,
                ];
            }

            $stripe = new \Stripe\StripeClient(env('STRIPE_KEY'));

            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',

                'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel'),

                'metadata' => [
                    'order_id' => $order->id,
                ],
            ]);

            $order->update([
                'session_id' => $session->id,
            ]);

            DB::commit();

            return Inertia::location($session->url);
        } catch (\Exception $e) {

            // dd($e->getMessage());

            return back()->with('error', 'Checkout failed');
        }
    }

    public function success(Request $request)
    {
        $sessionId = $request->session_id;

        if (! $sessionId) {
            return redirect()->route('cart.view')->with('error', 'Invalid session');
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_KEY'));

        $session = $stripe->checkout->sessions->retrieve($sessionId);

        $order = Order::where('session_id', $sessionId)->firstOrFail();

        if ($session->payment_status === 'paid') {

            DB::transaction(function () use ($order, $session) {

                $order->update([
                    'status' => 'paid',
                ]);

                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $order->total_price,
                    'payment_method' => 'stripe',
                    'transaction_id' => $session->payment_intent,
                    'status' => 'paid',
                ]);

                $userId = $order->paymentAddress->user_id;

                CartItem::where('user_id', $userId)->delete();
            });

            return redirect()->route('account.orders.index')
                ->with('success', 'Payment successful!');
        }

        return redirect()->route('cart.view')->with('error', 'Payment not completed');
    }

    public function cancel(Request $request)
    {
        return redirect()->route('cart.view')
            ->with('error', 'Payment was cancelled');
    }
}
