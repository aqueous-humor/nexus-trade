@extends('emails.layout')
@section('title', 'Withdrawal Approved')
@section('content')
<h2>Withdrawal Approved</h2>
<p>Your withdrawal request has been approved and is being processed.</p>
<div class="detail-row"><span class="label">Amount</span><span class="value">${{ number_format($transaction->amount_cents / 100, 2) }}</span></div>
<div class="detail-row"><span class="label">Destination</span><span class="value">{{ $transaction->destination_address }}</span></div>
@endsection
