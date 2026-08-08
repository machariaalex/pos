<x-errors.layout
    :code="404"
    title="Page not found"
    message="The page you're looking for doesn't exist or may have moved."
>
    @auth
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
    @else
        <a href="{{ route('login') }}" class="btn btn-primary">Go to Login</a>
    @endauth
</x-errors.layout>
