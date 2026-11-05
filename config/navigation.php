<?php

/*
|--------------------------------------------------------------------------
| Navegação do site (estrutura inicial)
|--------------------------------------------------------------------------
|
| Usada pelo MenuSeeder e enquanto os menus não forem editados no painel.
| 'route' é o nome da rota sem prefixo de idioma (ver lroute()); 'url' é um
| endereço externo. 'children_from' junta ao submenu o catálogo de serviços.
| O menu principal reproduz o do site antigo da APE.
|
*/

return [

    'main' => [
        ['label' => 'Início', 'route' => 'home'],
        ['label' => 'Vagas', 'route' => 'jobs'],
        ['label' => 'Serviços', 'route' => 'services', 'children_from' => 'service'],
        ['label' => 'Notícias', 'route' => 'blog'],
        ['label' => 'Sobre Nós', 'route' => 'about'],
        ['label' => 'Contactos', 'route' => 'contact'],
        ['label' => 'Visitar a ACB', 'url' => 'https://www.acb.co.mz', 'new_tab' => true],
    ],

    'footer' => [
        ['label' => 'Vagas', 'route' => 'jobs'],
        ['label' => 'Candidate-se já', 'route' => 'apply'],
        ['label' => 'Inscrever minha empresa', 'route' => 'companies'],
        ['label' => 'Parceiros', 'route' => 'partners'],
        ['label' => 'Notícias', 'route' => 'blog'],
        ['label' => 'Sobre Nós', 'route' => 'about'],
        ['label' => 'Contactos', 'route' => 'contact'],
    ],

    'legal' => [
        ['label' => 'Política de Privacidade', 'route' => 'privacy'],
        ['label' => 'Termos de Utilização', 'route' => 'terms'],
    ],

];
