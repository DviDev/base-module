<table class="table table-auto w-full overflow-auto">
    <thead>
    <tr>
        <th>Notificações</th>
        <th>Data</th>
        <th style="width: 10%"></th>
        <th style="width: 20%"></th>
    </tr>
    </thead>
    <tbody>
    @foreach($this->notifications() as $notification)
        <tr @class(["rounded-lg","bg-gray-100 dark:bg-gray-700" => $loop->odd])>
            <td @class(["text-center", "rounded-bl-lg" => $loop->last])>
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
                     "fa-eye text-green-600" => $notification->read_at,
                     "fa-eye-slash text-orange-600" => !$notification->read_at,
                 ])
                   title="{{$notification->read_at ? 'lido' : 'não lido'}}"
                ></i>
            </td>
            <td @class(["px-2 py-1","rounded-br-lg" => $loop->last])>
                <a href="{{route('notification', $notification->id)}}"
                   class="mt-1">
                    <x-flowbite::button :light="true" label="Detalhes" class="w-25 rounded text-sky-500"/>
                </a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
