@extends('emails.layout')
@section('title', 'Account Reactivated')
@section('content')
<h2>Account Reactivated</h2>
<p>Your trading account has been reactivated. You can now resume trading.</p>
<div class="detail-row"><span class="label">Account Type</span><span class="value">{{ ucfirst($account->type) }}</span></div>
@endsection
