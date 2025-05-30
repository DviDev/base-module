<tr>
    <td>
        <a href="{{route('admin.config', $config->id)}}" class="text-blue">
            {{$config->name}}
        </a>
    </td>
    <td>{{$config->value}}</td>
    <td>
        @if(!$config->default)
            <x-lte::button confirm icon="trash" danger action="delete"/>
        @endif
    </td>
</tr>
