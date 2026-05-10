@extends('emails.layout')
@section('title', 'Account Temporarily Locked')
@section('content')
<h2>Account Temporarily Locked</h2>
<p>Your account has been temporarily locked due to too many failed login attempts.</p>
<p>To regain access, please reset your password using the link below.</p>
<a href="{{ config('app.frontend_url', config('app.url')) }}/forgot-password" class="btn">Reset Password</a>
@endsection
