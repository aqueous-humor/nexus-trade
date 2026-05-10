@extends('emails.layout')
@section('title', 'Withdrawal Rejected')
@section('content')
<h2>Withdrawal Rejected</h2>
<p>Your withdrawal request has been rejected.</p>
<div class="detail-row"><span class="label">Amount</span><span class="value">${{ number_format($transaction->amount_cents / 100, 2) }}</span></div>
<div class="detail-row"><span class="label">Reason</span><span class="value">{{ $reason }}</span></div>
@endsection
