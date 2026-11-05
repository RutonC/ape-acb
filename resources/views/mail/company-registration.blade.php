<x-mail::message>
# Nova inscrição de empresa

<x-mail::table>
| | |
|:--|:--|
| **Empresa** | {{ $registration->company_name }} |
| **Nome** | {{ $registration->name }} |
| **Telefone** | {{ $registration->phone }} |
| **E-mail** | {{ $registration->email }} |
</x-mail::table>

<x-mail::button :url="route('admin', 'inscricoes-de-empresas')">
Abrir as inscrições no painel
</x-mail::button>
</x-mail::message>
