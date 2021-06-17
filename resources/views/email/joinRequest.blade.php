<h3>Join Request</h3>
    <table style="border-collapse: collapse;">
        <tr>    
            <td style="border: 1px solid #d8d0d0; padding: 5px 13px;">
            @if(isset($data))
            You are invited to join at {{ $data['case_first_name'] }} {{ $data['case_last_name'] }}'s case
            @endif
            </td>
        </tr> 
    </table>      