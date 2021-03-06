var getNextForm=function(identifier){
	elms=$(identifier);
	if(elms.length>0)
		return elms.first();
	return false;
};

var setAceEditor=function(elementId,readOnly){
	readOnly=readOnly || false;
	var editor = ace.edit(elementId);
	editor.setTheme("ace/theme/solarized_dark");
	editor.getSession().setMode({path:"ace/mode/php", inline:true});
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

var drawChart=function (title,rows,div) {

	// Create the data table.
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'Test case');
	data.addColumn('number', 'Time');
	data.addRows(rows);

	// Set chart options
	var options = {'title':title,'vAxis':{'minValue':0},'hAxis':{'minValue':0}};

	// Instantiate and draw our chart, passing in some options.
	var chart = new google.visualization.ColumnChart(document.getElementById(div));
	chart.draw(data, options);
}