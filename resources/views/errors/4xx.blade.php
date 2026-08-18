@extends('errors.layout')

@section('title', 'Request error')
@section('code', (string) $exception->getStatusCode())
@section('heading', 'Request could not be completed')
@section('message', 'Please check the address or return to the home page and try again.')
