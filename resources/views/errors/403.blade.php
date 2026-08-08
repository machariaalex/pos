<x-errors.layout
    :code="403"
    title="Access denied"
    message="You don't have permission to view this page. If you think this is wrong, ask an owner or manager to check your account's permissions."
>
    @auth
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
    @else
        <a href="{{ route('login') }}" class="btn btn-primary">Go to Login</a>
    @endauth
</x-errors.layout>
