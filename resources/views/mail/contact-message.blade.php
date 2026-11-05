<x-mail::message>
# Nova mensagem de contacto

Recebida pelo website em {{ $contactMessage->created_at->format('d/m/Y H:i') }}.

<x-mail::table>
| | |
|:--|:--|
| **Nome** | {{ $contactMessage->name }} |
| **Entidade** | {{ $contactMessage->organization ?: '—' }} |
| **E-mail** | {{ $contactMessage->email }} |
| **Telefone** | {{ $contactMessage->phone ?: '—' }} |
| **Assunto** | {{ $serviceLabel ?: '—' }} |
</x-mail::table>

**Mensagem**

{{ $contactMessage->message }}

<x-mail::button :url="route('admin', 'mensagens')">
Abrir a Caixa de entrada
</x-mail::button>

Responda a este e-mail para escrever directamente a {{ $contactMessage->name }}.
</x-mail::message>
