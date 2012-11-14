<table>
    <thead>
        <tr>
            <th>Movie Name22</th>
            <th>Release Year</th>
            <th>Director</th>
        </tr>
    </thead>
    <tbody>
{{each movies}}
        <tr>
            <td>${this.name}</td>
            <td>${this.year}</td>
            <td>${this.director}</td>
        </tr>
{{/each}}
    </tbody>
</table>
