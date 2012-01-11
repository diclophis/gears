<?php

class Paginator {

	public function __construct ($things, $request_parameters, $url_parameters, $total_things, $lpp = 20)
	{
		$this->things = $things;
		$this->request_parameters = $request_parameters;
		$this->url_parameters = $url_parameters;
		$this->total_things = $total_things; 
		$this->lpp = $lpp;
		$this->page = 1;
		$this->offset = 0;

		if (isset($this->request_parameters['page'])) {
			$this->page = $this->request_parameters['page'];
		}

		if ($this->page > 1) {
			$this->offset = $this->lpp * ($this->page - 1);
		}

		// The count of how many "number" pagination controls will exist
		// not including the "prev, next, first, last" controls
		// in my case, there are 7 "number control" slots
		$this->total_number_ctrls =  7; 

		$this->total_pages = ceil($this->total_things/$this->lpp);		
		
		// the controls vary depending on what page you are on...
		$this->start_of_range = '';
		$this->range_size = '';
		
		// ** DEFINE START OF RANGE AND RANGE SIZE 
		
		// if there are fewer pages than the allotted space
		if ($this->total_pages <= $this->total_number_ctrls) {
			// show all numbers (no ellipses)
			$this->start_of_range = 1;
			$this->range_size = $this->total_number_ctrls;
			$this->special = "false";
			// there are more pages of results than there are number control slots - there are some page number controls that are hidden
			// and ellipses to show that there are more pages
		} else {
			// page falls within first range: only has trailing ellipses
			if ($this->page < $this->total_number_ctrls) {
				$this->start_of_range = 1;
				$this->range_size = $this->total_number_ctrls-1;
			// page is higher than the first range - all of these will for sure have leading ellipses
			} else {
				// start of range is always a multiple of 5 plus 2  ?? test this: a multiple of ($this->total_numer_ctrls-2)+2 ??
				// find the start of range in terms of $this->page and $this->total_pages
				// check if the selected page is a potential start of range
				if (($this->page - $this->total_number_ctrls) % ($this->total_number_ctrls - 2) == 0) {
					// check for case where last page number ctrl fits perfectly in the last slot
					// instead of being the start of a new range the page ctrl goes in the last slot
					if ($this->total_pages == $this->page) {
						// the start of range is the next one lower
						$this->start_of_range = $this->page - ($this->total_number_ctrls-2);
						$this->range_size = ($this->total_number_ctrls-1); // no trailing ellipses
					} else {
						// this page is the start of a range
						$this->start_of_range = $this->page;
						
						// range could have trailing ellipses or blanks at the end...
						// is this the last range?
						if ($this->page + ($this->total_number_ctrls-2) > $this->total_pages) {
							$this->range_size = ($this->total_number_ctrls-1); // leading ellipses and blanks at the end
						} else {
							$this->range_size = ($this->total_number_ctrls-2); // both leading and trailing ellipses
						}
					}
					$this->special = "false";
				} else {
					// the selected page is NOT the start of range
					$this->special = "true";
					// if the page is in the second range, it's start of range is equal to the number of number ctrl slots
					if ($this->page < $this->total_number_ctrls + ($this->total_number_ctrls-2)) {
						$this->start_of_range = $this->total_number_ctrls;
					} else {
						// third range or greater
						$this->start_of_range = ((ceil(($this->page - $this->total_number_ctrls)/($this->total_number_ctrls-2))) * ($this->total_number_ctrls-2)) + 2;
					}
					
					// range could have trailing ellipses or blanks at the end...
					// if this is the last range
					if ($this->start_of_range + ($this->total_number_ctrls-2) > $this->total_pages) {
						$this->range_size = ($this->total_number_ctrls-1); // leading ellipses and blanks at the end
					// not the last range
					} else {
						$this->range_size = ($this->total_number_ctrls-2); // both leading and trailing ellipses
					}
				}	
			}
		}

      // load the set of page controls to display
		$this->page_ctrls = array();
		$parameters_for_page_urls = $url_parameters;
		for ($j=$this->start_of_range; $j <= ($this->start_of_range + $this->range_size - 1); $j++) {
			$parameters_for_page_urls["page"] = $j;
			$this->page_ctrls[$j] = Dispatcher::get_url($parameters_for_page_urls);
		}

		// calculate the various movement values:
		$this->prev_page = ($this->page > 1) ? $this->page - 1 : $this->page;
		$this->prev_range = ($this->start_of_range > 1) ? ($this->start_of_range - 1) : 1;
		$this->next_range = (($this->total_pages > $this->total_number_ctrls) && (($this->start_of_range + $this->total_number_ctrls -1) < $this->total_pages))
		? ($this->start_of_range + $this->range_size) : 0;
		$this->next_page = ($this->page < $this->total_pages) ? ($this->page + 1) : $this->total_pages;
		
		// get values for the range of listing records displayed on this page
		$this->first_thing_on_page = ($this->page * $this->lpp) - $this->lpp + 1;
		$this->last_thing_on_page = $this->page * $this->lpp;
		if ($this->last_thing_on_page > $this->total_things) {
			$this->last_thing_on_page = $this->total_things;
		}

		$parameters_for_previous_page_url = $url_parameters;
		$parameters_for_previous_page_url['page'] = $this->prev_page;

		$parameters_for_previous_range_url = $url_parameters;
		$parameters_for_previous_range_url['page'] = $this->prev_range;

		$this->previous_page_url = Dispatcher::get_url($parameters_for_previous_page_url);
		$this->previous_range_url = Dispatcher::get_url($parameters_for_previous_range_url);

		$parameters_for_next_page_url = $url_parameters;
		$parameters_for_next_page_url['page'] = $this->next_page;

		$parameters_for_next_range_url = $url_parameters;
		$parameters_for_next_range_url['page'] = $this->next_range;
		$this->next_range_url = Dispatcher::get_url($parameters_for_next_range_url);
		$this->next_page_url = Dispatcher::get_url($parameters_for_next_page_url);

		//{url_for action=$current_action acnt=$acnt page=$next_range lpp=$lpp}
	}
}

?>
