<% if $Cookies %>
    <div class="table-responsive">
        <table class="table cookie-group-table">
            <thead class="cookie-group-table__header">
            <tr class="cookie-group-table__header-row">
                <th class="cookie-group-table__header-col cookie-group-table__header-col--name"><%t XD\\CookieConsent\\Shortcode\\CookieGroupTable.Title 'Name cookie' %></th>
                <th class="cookie-group-table__header-col cookie-group-table__header-col--provider"><%t XD\\CookieConsent\\Shortcode\\CookieGroupTable.Provider 'Placed by' %></th>
                <th class="cookie-group-table__header-col cookie-group-table__header-col--purpose"><%t XD\\CookieConsent\\Shortcode\\CookieGroupTable.Purpose 'Purpose' %></th>
                <th class="cookie-group-table__header-col cookie-group-table__header-col--expiry"><%t XD\\CookieConsent\\Shortcode\\CookieGroupTable.Expiry 'Expiry' %></th>
            </tr>
            </thead>
            <tbody class="cookie-group-table__body">
            <% loop $Cookies %>
                <tr class="cookie-group-table__body-row">
                    <td class="cookie-group-table__body-col cookie-group-table__body-col--name">$Title</td>
                    <td class="cookie-group-table__body-col cookie-group-table__body-col--provider">$Provider</td>
                    <td class="cookie-group-table__body-col cookie-group-table__body-col--purpose">$Purpose</td>
                    <td class="cookie-group-table__body-col cookie-group-table__body-col--expiry">$Expiry</td>
                </tr>
            <% end_loop %>
            </tbody>
        </table>
<% end_if %>
</div>
