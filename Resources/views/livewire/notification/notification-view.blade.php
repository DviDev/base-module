<diiv>

    <label @class(['font-bold text-gray-700'])>Conteúdo</label>
    <div class="mb-2">
        {{$notification->data['title']}}
    </div>
    <label @class(['font-bold text-gray-700'])>Descrição</label>
    <div class="mb-2">
        {!! nl2br(e($notification->data['description'])) !!}
    </div>
    @if($user)
        <label @class(['font-bold text-gray-700'])>User</label>
        <div class="mb-2">
            @if($user->id == auth()->user()->id)
                {{$user->name}}
            @elseif( ($user?->isAdmin()))
                Administrador
            @endif
        </div>
    @endif
    <label @class(['font-bold text-gray-700'])>Data</label>
    <div class="mb-2">
        <div>{{(new \Carbon\Carbon($notification->created_at))->format('d/m/Y H:i')}}</div>
    </div>
    @if($action =  $this->getAction())
        <div class="my-4">
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
</diiv>
