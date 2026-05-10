@extends('emails.layout')
@section('title', 'Investment Created')
@section('content')
<h2>Investment Created</h2>
<p>Your investment has been created and is pending activation.</p>
<div class="detail-row"><span class="label">Plan</span><span class="value">{{ $investment->plan->name }}</span></div>
<div class="detail-row"><span class="label">Amount</span><span class="value">${{ number_format($investment->amount_cents / 100, 2) }}</span></div>
<div class="detail-row"><span class="label">Duration</span><span class="value">{{ $investment->duration->label }}</span></div>
<div class="detail-row"><span class="label">Matures At</span><span class="value">{{ $investment->maturity_at->format('M d, Y H:i') }} UTC</span></div>
<div class="detail-row"><span class="label">Status</span><span class="value">Pending</span></div>
@endsection
