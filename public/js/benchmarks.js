const getNextForm = function (identifier) {
	const elms = $(identifier);
	if (elms.length > 0)
		return elms.first();
	return false;
};

const setAceEditor = function (elementId, readOnly,aceTheme) {
	readOnly = readOnly || false;
	aceTheme = aceTheme || 'solarized_dark';
	const editor = ace.edit(elementId);
	editor.setTheme("ace/theme/"+aceTheme);
	editor.getSession().setMode({path: "ace/mode/php", inline: true});
	editor.setOptions({
		maxLines: 10,
		minLines: 2,
		showInvisibles: true,
		showGutter: !readOnly,
		showPrintMargin: false,
		readOnly: readOnly,
		showLineNumbers: !readOnly,
		highlightActiveLine: !readOnly,
		highlightGutterLine: !readOnly
	});
};

const drawChart = function (title, rows, div) {

	// Create the data table.
	const data = new google.visualization.DataTable();
	data.addColumn('string', 'Test case');
	data.addColumn('number', 'Time');
	data.addRows(rows);

	// Set chart options
	const options = {'title': title, 'vAxis': {'minValue': 0}, 'hAxis': {'minValue': 0}};

	// Instantiate and draw our chart, passing in some options.
	const chart = new google.visualization.ColumnChart(document.getElementById(div));
	chart.draw(data, options);
};