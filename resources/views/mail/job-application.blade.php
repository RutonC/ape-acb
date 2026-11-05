<x-mail::message>
# Nova candidatura

**{{ $application->positionLabel() }}**, recebida pelo site em {{ $application->created_at->format('d/m/Y H:i') }}.

O currículo e os dados completos estão no painel.

<x-mail::button :url="route('admin', 'candidaturas/'.$application->id)">
Abrir a candidatura no painel
</x-mail::button>
</x-mail::message>
