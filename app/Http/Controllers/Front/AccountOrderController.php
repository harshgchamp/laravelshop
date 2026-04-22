<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserAddress;
use Inertia\Inertia;

class AccountOrderController extends Controller
{
    public function index()
    {
        $userAddressIds = UserAddress::where('user_id', auth()->id())
            ->pluck('id');

        $orders = Order::with('items.product')
            ->whereIn('user_address_id', $userAddressIds)
            ->latest()
            ->paginate(10);

        // dd($orders->toArray());

        return Inertia::render('Front/Account/Orders/Index', [
            'orders' => $orders,
            'user' => auth()->user(),
        ]);
    }

    public function show(Order $order)
    {
        // Ensure the order belongs to the authenticated user
        if ($order->paymentAddress->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $order->load([
            'items.product',
            'paymentAddress',
            'paymentDetails',

        ]);

        //  dd($order->toArray());

        return Inertia::render('Front/Account/Orders/Show', [
            'order' => $order,
            'user' => auth()->user(),
        ]);
    }
}
