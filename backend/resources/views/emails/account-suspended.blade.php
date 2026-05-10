@extends('emails.layout')
@section('title', 'Account Suspended')
@section('content')
<h2>Account Suspended</h2>
<p>Your trading account has been suspended. Please contact support for assistance.</p>
<div class="detail-row"><span class="label">Account Type</span><span class="value">{{ ucfirst($account->type) }}</span></div>
@endsection
