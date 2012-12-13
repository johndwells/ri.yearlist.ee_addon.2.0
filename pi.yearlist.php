<?php
/*
================================================================
	Year List
	for EllisLab ExpressionEngine - by Ryan Irelan	
----------------------------------------------------------------
	Copyright (c) 2009 Mijingo, LLC
================================================================
	THIS IS COPYRIGHTED SOFTWARE. PLEASE
	READ THE LICENSE AGREEMENT.
----------------------------------------------------------------
	This software is based upon and derived from
	EllisLab ExpressionEngine software protected under
	copyright dated 2005 - 2008. Please see
	http://expressionengine.com/docs/license.html
----------------------------------------------------------------
	USE THIS SOFTWARE AT YOUR OWN RISK. WE ASSUME
	NO WARRANTY OR LIABILITY FOR THIS SOFTWARE AS DETAILED
	IN THE LICENSE AGREEMENT.
================================================================
	File:			pi.yearlist.php
----------------------------------------------------------------
	Version:		2.0
----------------------------------------------------------------
	Purpose:	  Returns list of years in which there are entries
----------------------------------------------------------------
	Compatibility:	EE 2.0
----------------------------------------------------------------
	Created:		2009-04-03
================================================================
*/

// -----------------------------------------
//	Information array
// -----------------------------------------

$plugin_info = array(
                 'pi_name'          => 'Year List',
                 'pi_version'       => '2.0.2',
                 'pi_author'        => 'Ryan Irelan',
                 'pi_author_url'    => 'http://eeinsider.com',
                 'pi_description'   => 'Returns list of years in which there are entries',
                 'pi_usage'         => Yearlist::usage()
               );

// -----------------------------------------
//	Begin class
// -----------------------------------------

class Yearlist
{
    var $return_data;
    var $category;
	  var $channel;
	
    // -------------------------------
    // Constructor
    // -------------------------------
    
    function Yearlist ()
    {
		// super object
		$this->EE =& get_instance();		
		
		// --------------------------
		// get the channel parameter
		// --------------------------
		$channel = $this->EE->TMPL->fetch_param('channel');
		if (!$this->EE->TMPL->fetch_param('channel'))
		{
			$error = "You did not provide a channel name, so this will not work!";
			return $error;
		} 	                                                                      
		// ---------------------------
		// get the category parameter
		// ---------------------------
		if ($this->EE->TMPL->fetch_param('category') !=false)
		{
			$category = $this->EE->TMPL->fetch_param('category');
		}
		else
		{
			$category = 'all';
		}                                              
		// ---------------------------
		// get the year sort parameter
		// ---------------------------
		if ($this->EE->TMPL->fetch_param('sort') !=false)
		{
			$sort = $this->EE->TMPL->fetch_param('sort');
		}
		else
		{
			$sort = 'desc';
		}                                              
		// ---------------------------
		// get the status parameter
		// ---------------------------
		if ($this->EE->TMPL->fetch_param('status') !=false)
		{
			$status = $this->EE->TMPL->fetch_param('status');

			// multiple?
			$status = preg_split("/[\|,]/", $status );
		}
		else
		{
			$status = false;
		}                                              
		
		// ---------------------------
		// Query the database
		// ---------------------------
		$query = $this->EE->db->select('channel_id');
		$query = $this->EE->db->get_where('exp_channels', array('channel_name' => $channel));
		
		// ----------------------------
		// Is this a real channel name?
		// ----------------------------
		if ($query->num_rows == 0)
		{
			$error = "The channel name you provided does not exist. Please check your channel name and try again.";
			return $error;
		}
		
		// ------------------------------
		// Build the query to get years
		// ------------------------------  
		foreach ($query->result() as $row)
		{
			$channel = $row->channel_id;
		}
		
		if ($category == 'all')
		{
			$this->EE->db->select('year')->distinct()->from('exp_channel_titles')->where('channel_id', $channel)->order_by('year', $sort);		
		}
		else
		{
			// --------------------------------------------------
			// if the category is set to something besides all
			// we need to query for only entries that are in that
			// category
			// ---------------------------------------------------                               
			$wheres = array('exp_channel_titles.channel_id' => $channel, 'exp_category_posts.cat_id' => $category);
			
			$this->EE->db->select('exp_channel_titles.year')->distinct()->from('exp_channel_titles')->join('exp_category_posts', 'exp_channel_titles.entry_id = exp_category_posts.entry_id', 'inner')->where($wheres)->order_by('year', $sort);
		}

		// status?
		if($status)
		{
			$this->EE->db->where_in('status', $status);
		}

		// do the query 
		$query = $this->EE->db->get();   
		
		// ----------------------------
		// Return query and parse tags
		// ----------------------------
		if ($query->num_rows() == 0)
		{
			$this->return_data = "";
		}                           
		else
		{
			foreach ($query->result() as $row)
			{  
				$tagdata = $this->EE->TMPL->tagdata;
				
				foreach ($this->EE->TMPL->var_single as $key => $val)
				{
					if ($key == 'year')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, $row->year, $tagdata);
					}
				}                                                                     
				$this->return_data .= $tagdata;
			}
		}
    }
    // END
	
	// -------------------------------
    // Usage
    // -------------------------------

	function usage()
	{
		ob_start(); 
?>
The Year Listing plugin is a simple way to get a distinct 4 digit year for your entries. This way you can list out years for archives.

{exp:yearlist channel="yourchannel" category="1" sort="desc"}

{year}

{/exp:yearlist}

That will return an array of years. Use {year} to print them to the screen and wrap in any markup needed. There are currently no linebreaks or HTML associated with this plugin.

The category parameter is optional and if you leave it out, the plugin will search across all categories. There is currently no support for having multiple categories in the category parameter (e.g. category="3|8|10"). This may come later.

The sort parameter is optional and if you leave it out, the plugin will return the years in descending order.

The status parameter is optional and if you leave it out, it will query ANY status. Separate multiple statuses with either a pipe or comma.

<?php
		$buffer = ob_get_contents();
		ob_end_clean(); 

		return $buffer;
	}
	// END
	
}
// END CLASS
?>