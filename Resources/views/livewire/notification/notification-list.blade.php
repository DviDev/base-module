<x-lte::card card_id="notifications" :header="str(__('notifications'))->ucfirst()" class="shadow-none border-0">
    <x-lte::card.body>
        <table class="table table-sm">
            <thead>
            <tr>
                <th>Notificações</th>
                <th>Data</th>
                <th style="width: 10%"></th>
                <th style="width: 20%"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($notifications = $this->notifications() as $notification)
                <tr>
                    <td class="text-sm">
                        <div>{{$notification->data['title']}}</div>
                        <div class="text-gray-400">{{str($notification->data['description'])->limit(60)}}</div>
                    </td>
                    <td class="h-full">
                        <div class="flex justify-content-start items-center">
                            <span>{{(new \Carbon\Carbon($notification->created_at))->format('Y-m-d H:i')}}</span>
                        </div>
                    </td>
                    <td>
                        <i @class([
                            "fas fa-1x",
                             "fa-eye text-success" => $notification->read_at,
                             "fa-eye-slash text-warning" => !$notification->read_at,
                             ])
                           title="{{$notification->read_at ? 'lido' : 'não lido'}}"
                        ></i>
                    </td>
                    <td>
                        <a href="{{route('notification', $notification->id)}}"
                           class="btn btn-outline-primary btn-sm mt-1">
                            <i class="fas fa-eye"></i> Detalhes
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-lte::card.body>
    @if($notifications->total() > $notifications->perPage())
        <x-lte::card.footer class="flex">
            {{$notifications->links()}}
        </x-lte::card.footer>
    @endif
</x-lte::card>
