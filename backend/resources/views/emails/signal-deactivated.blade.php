@extends('emails.layout')
@section('title', 'Signal Deactivated')
@section('content')
<h2>Signal Deactivated</h2>
<p>A trading signal you were subscribed to has been deactivated. Your account has been automatically unsubscribed.</p>
<div class="detail-row"><span class="label">Signal</span><span class="value">{{ $signal->name }}</span></div>
@endsection
