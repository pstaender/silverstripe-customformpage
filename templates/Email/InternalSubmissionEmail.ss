<h1>
    <%t InternalSubmissionEmail.NewSubmissionReceivedOnPage "New {pagetitle} form submission" pagetitle=$Submission.Page.Title %>
</h1>
<h2>
    <%t InternalSubmissionEmail.NewSubmissionReceivedFrom "From {name}<{email}>" name=$Submission.Data.Name email=$Submission.Data.Email %>
</h2>
<h3>
    <%t InternalSubmissionEmail.SubmittedOn "Submitted on {date}" date=$Submission.Created.Nice %>
<p>
<% if $Submission.DataAsList %>
    <table>
        <thead>
        <tr>
            <th style="visibility: hidden;">
                <%t InternalSubmissionEmail.Key 'Key' %>
            </th>
            <th style="visibility: hidden;">
                <%t InternalSubmissionEmail.Value 'Value' %>
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