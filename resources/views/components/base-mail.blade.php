@props([
'body' => null,
])
<div style="background-color:#fffbfb; font-family:Arial,Helvetica,sans-serif; line-height: 1.5; min-height: 100%; font-weight: normal; font-size: 15px; color: #2F3044; margin:0; padding:40px 0; width:100%; border-radius: 5px;">
    <div style="background-color:#ffffff; padding: 45px 0 34px 0; border-radius: 24px; margin:40px auto; max-width: 600px;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse">
            <tbody>
                <tr>
                    <td valign="center" style="padding-bottom: 10px">
                        <!--begin:Email content-->
                        <div style="margin:0 15px 34px 15px">
                            <!--begin:Logo-->
                            <div style="text-align:center; margin-bottom: 50px; background-color: #2daf54; border-radius: 10px; padding: 20px 0">
                                <a href="#" rel="noopener" target="_blank">
                                    <img alt="Logo" src="#" style="height: 35px" />
                                </a>
                            </div>
                            <!--end:Logo-->

                            <!--begin:Text-->
                            <div style="font-size: 14px; font-weight: 500; padding: 0 5px; margin-bottom: 27px; font-family:Arial,Helvetica,sans-serif;">
                                {!! $body ?? $slot ?? null !!}
                            </div>
                            <!--end:Text-->

                            <div style="text-align: center">
                                <a href="#" target="_blank" style="text-decoration: none; background-color:#2daf54; border-radius:6px;display:inline-block; padding:11px 19px; color: #FFFFFF; font-size: 14px; font-weight:500;">
                                    Shoot90
                                </a>
                            </div>
                        </div>
                        <!--end:Email content-->
                    </td>
                </tr>
                <tr>
                    <td align="center" valign="center" style="text-align:center; padding-bottom: 20px;">
                        <a href="#" target="_blank" style="margin-right:10px"><img alt="linkedin" src="" /></a>
                        <a href="#" target="_blank" style="margin-right:10px"><img alt="twitter" src="" /></a>
                        <a href="#" target="_blank" style="margin-right:10px"><img alt="facebook" src="" /></a>
                    </td>
                </tr>

                <tr>
                    <td align="center" valign="center" style="font-size: 13px; padding:0 15px; text-align:center; font-weight: 500; color: #A1A5B7;font-family:Arial,Helvetica,sans-serif">
                        <p> © 2024 Shoot90.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>