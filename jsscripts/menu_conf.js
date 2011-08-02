// menu_items.js file

var MENU_ITEMS =[
	["home", "http://192.168.210.189:/dwi/index.php"],
	["quick links", null,
		["control center", 'https://216.75.205.134/control_center/']
	],
	["log out", "http://192.168.210.189:/dwi/index.php"]
];


/* --- geometry and timing of the menu --- */
var MENU_POS = new Array();

	// item sizes for different levels of menu
	MENU_POS['height']     = [20];
	MENU_POS['width']      = [200];

	// menu block offset from the origin:
	//  for root level origin is upper left corner of the page
	//  for other levels origin is upper left corner of parent item
	MENU_POS['block_top']  = [0];
	MENU_POS['block_left'] = [0];

	// offsets between items of the same level
	MENU_POS['top']        = [0];
	MENU_POS['left']       = [50];

	// time in milliseconds before menu is hidden after cursor has gone out
	// of any items
	MENU_POS['hide_delay'] = [200, 200];

/* --- dynamic menu styles ---
note: you can add as many style properties as you wish but be not all browsers
are able to render them correctly. The only relatively safe properties are
'color' and 'background'. */
var MENU_STYLES = new Array();

	// default item state when it is visible but doesn't have mouse over
	MENU_STYLES['onmouseout'] = [
		'color', ['#b5c5da', '#b5c5da'],
		'background', ['#587cac', '#587cac'],
	];

	// state when item has mouse over it
	MENU_STYLES['onmouseover'] = [
		'color', ['#b5c5da', '#b5c5da'],
		'background', ['#587cac', '#587cac'],
	];

	// state when mouse button has been pressed on the item
	MENU_STYLES['onmousedown'] = [
		'color', ['#a3bbdc', '#a3bbdc'],
		'background', ['#587cac', '#587cac'],
	];