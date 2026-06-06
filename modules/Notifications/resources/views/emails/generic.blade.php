<x-mail::message>
# {{ $subjectLine }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
</x-mail::message>
