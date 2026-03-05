
<table>
<thead>
    <tr>
        @foreach($headers as $header)
            <th>{{ $header }}</th>
        @endforeach
    </tr>
</thead>
<tbody>
    @foreach($results as $row)
        <tr>
            @foreach($headers as $header)
                <td>{{ $row->$header ?? '-' }}</td>
            @endforeach
        </tr>
    @endforeach
</tbody>
</table>