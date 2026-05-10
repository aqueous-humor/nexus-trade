@extends('emails.layout')
@section('title', 'Investment Completed')
@section('content')
<h2>Investment Completed</h2>
<p>Your investment has been completed.</p>
<div class="detail-row"><span class="label">Result</span><span class="value"><span class="badge badge-{{ strtolower($investment->result) }}">{{ $investment->result }}</span></span></div>
<div class="detail-row"><span class="label">Amount Invested</span><span class="value">${{ number_format($investment->amount_cents / 100, 2) }}</span></div>
<div class="detail-row"><span class="label">Profit</span><span class="value">${{ number_format($investment->profit_cents / 100, 2) }}</span></div>
@endsection
