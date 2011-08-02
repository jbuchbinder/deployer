var confParms = {
	"style": {
		"width": 830,
		"height":200,
		"view": { 
				"width": 830, "height":200, 
				"offset": { "x":0, "y":0 
			} 
		},
		"bgcolor" : "#B6C4D1"
	},
	"comment": 
	{ 
		"width":100,                  // Width of the comment area 
                                             // in _text_columns_, not in pixels

		"height":10,                  // Height of the comment area 
                                             // in _text_columns_, not in pixels

		"offset": { "x":0, "y":0 },  // Offset of the comment area within a widget.
                "style":
                {
			"bgcolor":"red", 
			"color":"gray", 
			"css":"txt"
		}
	},
	"position": { "x":0, "y":0, "absolute" : false },
	"scroll": {
		"type":"control",
		"step":5,
		"timer":20,
		"dir":"s",
		"pause":100,
		"cycle":true,
		"ctrlstyle": { 
				"width":60, 
				"height":25, 
				"align":"center" 
			     },
		"ctrlpos": { "x":320, "y":185 },
		"control": [
			{ "type":"link", 
			  "act":"prev", 
			  "text":"[ Prev ]" 
			},
			{ "type":"link", 
			  "act":"next", 
			  "text":"[ Next ]" 
			},
			{ "type":"link", 
			  "act":"rew", 
			  "text":"[ Rew  ]" 
			}
		]
	},
	"itemstyle":{ "bgcolor": "white" },
	"items": [
		{
			"type":"HTML",
			"src":"<p>Control Center got a huge UI update to bring it's look and feel inline with the rest of the customer facing applications.</p>" , 
			"style": {
				"bgcolor":"#ffffff",
				"color":"#000000"
			}
		},
		{
			"type":"HTML",
			"src":"<p>Late last week all of the internal sites were moved until the umbrella of the DWI site.  If you notice any issues see Dave or Sean.</p>",
			"style": {
				"bgcolor":"#ffffff",
				"color":"#000000"
			}
		},
		
	]
};


