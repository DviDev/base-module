<x-lte::card card_id="notificação" class="shadow-none border-0">
    <x-lte::card.body>
        <label>Conteúdo</label>
        <div class="mb-2">
            {{$notification->data['title']}}
        </div>
        <label>Descrição</label>
        <div class="mb-2">
            {!! nl2br(e($notification->data['description'])) !!}
        </div>
        @if($user)
            <label>User</label>
            <div class="mb-2">
                @if($user->id == auth()->user()->id)
                    {{$user->name}}
                @elseif( ($user?->isAdmin()))
                    Administrador
                @endif
            </div>
        @endif
        <label>Data</label>
        <div class="mb-2">
            <div>{{(new \Carbon\Carbon($notification->created_at))->format('d/m/Y H:i')}}</div>
        </div>
        @if($action =  $this->getAction())
            <div class="mt-2">
                <x-dvui::link md
                              :url="$action->url" :text="$action->text"
                              :btn="$action->btn"
                              :primary="$action->type == 'info'"
                              :success="$action->type == 'success'"
                              :warning="$action->type == 'warning'"
                              :danger="$action->type == 'danger'"
                              :secondary="!$action->type"
                />
            </div>
        @endif
    </x-lte::card.body>
    <x-lte::card.footer>
        <a href="{{url()->previous()}}" class="btn btn-default">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </x-lte::card.footer>
</x-lte::card>
