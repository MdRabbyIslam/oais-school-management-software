<!-- School Header with Logo -->
{{-- <table class="header-table">
    <tr>
        <td class="logo-cell">
            <img alt="logo" src="{{ public_path("upload/images/Logo__Oysis.png") }}" style="max-width: 60px; max-height: 60px;">
        </td>
        <td class="info-cell">
                <div class="school-name">{{ config('app.school_name','Oasis Model School') }}</div>
            <div class="school-motto">"Knowledge, Wisdom, Excellence"</div>
            <div class="school-address">
                {{ config('app.school_address') }}<br>
                Phone: {{ config('app.school_phone') }} | Email: {{ config('app.school_email') }}
            </div>
        </td>
        <td class="logo-cell"></td>

    </tr>
</table> --}}


<table style="width: 100%">
    <tr>
        {{-- theree td in a row first and last td will be 40px width and middle td will take rest of the width --}}
        <td style="width: 50px">
                        <img alt="logo" src="{{ public_path("upload/images/Logo__Oysis.png") }}" style="max-width: 50px; max-height: 50px;">

        </td>
        <td style="text-align: center; width: 100%">
            <div class="invoice-title">
                <div class="school-name">{{ config('app.school_name','Oasis Model School') }}</div>

            <div class="school-address">
                {{ config('app.school_address') }}<br>
                Phone: {{ config('app.school_phone','01987-683820,01318-955709') }}
            </div>
            </div>
        </td>
        <td style="width: 50px"></td>
    </tr>
</table>


