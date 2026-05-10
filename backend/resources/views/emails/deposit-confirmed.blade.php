@extends('emails.layout')
@section('title', 'Deposit Confirmed')
@section('content')
<h2>Deposit Confirmed</h2>
<p>Your deposit has been confirmed and credited to your wallet.</p>
<div class="detail-row"><span class="label">Currency</span><span class="value">{{ $transaction->currency }}</span></div>
<div class="detail-row"><span class="label">Amount</span><span class="value">${{ number_format($transaction->amount_cents / 100, 2) }}</span></div>
<div class="detail-row"><span class="label">Fee</span><span class="value">${{ number_format($transaction->fee_cents / 100, 2) }}</span></div>
<div class="detail-row"><span class="label">Net Credited</span><span class="value">${{ number_format($transaction->net_amount_cents / 100, 2) }}</span></div>
@if($transaction->exchange_rate && $transaction->currency !== 'USD')
<div class="detail-row"><span class="label">Exchange Rate</span><span class="value">1 {{ $transaction->currency }} = ${{ $transaction->exchange_rate }}</span></div>
@endif
@endsection
