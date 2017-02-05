<h1>New $Submission.Page.Title form submission</h1>
<h2><% if $Submission.Data.Name %> by $Submission.Data.Name<% end_if %><% if $Submission.Data.Email %> from $Submission.Data.Email<% end_if %></h2>
<h3>Received on $Submission.Created.Nice</h3>

<p>
<% if $Submission.DataAsList %>
    <table>
        <thead>
        <tr>
            <th style="visibility: hidden;">
                Field
            </th>
            <th style="visibility: hidden;">
                Value
            </th>
        </tr>
        </thead>
        <tbody>
            <% loop $Submission.DataAsList %>
            <tr style="<% if Odd %>background: #ddd;<% end_if %>">
                <td style="padding-right: 1em; font-weight: bold;">$Key</td>
                <td>
                    <pre>$Value</pre>
                </td>
            </tr>
            <% end_loop %>
        </tbody>
        </tbody>
    </table>

<% end_if %>
</p>