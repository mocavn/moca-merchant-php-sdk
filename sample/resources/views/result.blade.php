@extends('layout.app')
@section('content')
    <section id="cart_items">
        <div class="container">
            <div class="breadcrumbs">
                <ol class="breadcrumb">
                    <li><a href="#">Home</a></li>
                    <li class="active">Result payment</li>
                </ol>
            </div>
            <div class="heading">
                @isset($result)
                    @if ($result->code ==200)
                        <h3>Result of transaction is success</h3>
                        <p>TxID: {{$result->body->txID}}</p>
                    @else
                        <h3>Result of transaction is failed</h3>
                    @endif


                @endisset
            </div>
        </div>
    </section>
@endsection
