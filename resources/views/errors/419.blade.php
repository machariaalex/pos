<x-errors.layout
    :code="419"
    title="Session expired"
    message="This page was left open too long and your session timed out. Please reload and try again."
>
    <a href="{{ url()->previous() }}" class="btn btn-primary">Reload page</a>
</x-errors.layout>
