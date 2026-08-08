{{-- No @auth/DB-dependent logic here — a 500 can be caused by exactly
     that being broken, and this page must still render regardless. --}}
<x-errors.layout
    :code="500"
    title="Something went wrong"
    message="An unexpected error occurred. Please try again, and let the owner know if this keeps happening."
>
    <a href="{{ url('/') }}" class="btn btn-primary">Go to homepage</a>
</x-errors.layout>
