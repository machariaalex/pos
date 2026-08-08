{{-- No @auth/DB-dependent logic here — maintenance mode and DB outages
     are exactly the scenarios this page needs to survive. --}}
<x-errors.layout
    :code="503"
    title="Down for maintenance"
    message="The system is briefly offline for maintenance. Please check back in a few minutes."
>
    <a href="{{ url('/') }}" class="btn btn-primary">Try again</a>
</x-errors.layout>
