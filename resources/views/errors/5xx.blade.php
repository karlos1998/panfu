@extends('errors.layout')

@section('title', 'Server error')
@section('code', (string) $exception->getStatusCode())
@section('heading', 'Something went wrong')
@section('message', 'We could not complete your request. Please try again in a moment.')
