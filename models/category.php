<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class category_class extends AWS_MODEL
{
	/**
	 * 获取分类列表
	 */
	public function get_category_list($type, $return_count = false, $parent_id = 0, $order = 'sort ASC', $child_count = true)
	{		
		$category_list = array();
		
		if ($return_count)
		{
			return count($this->model('system')->fetch_category_data($type, $parent_id));
		}
		
		if (!$category_all = $this->model('system')->fetch_category_data($type, $parent_id, $order))
		{
			return $category_list;
		}
		
		foreach ($category_all as $key => $val)
		{
			$category_all[$key]['child_count'] = $this->get_category_list($type, true, $val['id']);
		}
		
		return $category_all;
	}

	public function get_category_position($category_id, $position = array())
	{
		$category = $this->get_category_by_id($category_id);
		
		if($category)
		{
			$position[] = $category;
		}
	
		if ($category['parent_id'] == 0)
		{
			$position[] = array(
				'id' => '0',
				'title' => '顶级分类',
			);
			return array_reverse($position);
		}
		else if ($category['parent_id'] > 0)
		{
			return $this->get_category_position($category['parent_id'], $position);
		}
	}
	
	public function get_category_by_id($category_id)
	{
		if(!$category_id)
		{
			return false;
		}
		
		if (is_array($category_id))
		{
			if (sizeof($category_id) == 0)
			{
				return false;
			}
			
			$category_ids = $category_id;
		}
		else
		{
			$category_ids = array(
				intval($category_id)
			);
		}
		
		array_walk_recursive($category_ids, 'intval_string');
		
		if ($rs = $this->fetch_all('category', 'id IN (' . implode(',', $category_ids) . ')'))
		{
			$data = array();
			
			foreach($rs as $key => $val)
			{
				if(!$val['url_token'])
				{
					$rs[$key]['url_token'] = $val['id'];
				}
				
				$data[$val['id']] = $rs[$key];
			}
		}
		
		if (is_array($category_id))
		{
			return $data;
		}
		else
		{
			return $rs[0];
		}
	}
	
	public function update_category($category_id, $update_arr)
	{
		return $this->update('category', $update_arr, 'id = ' . intval($category_id));
	}
	
	public function add_category($type, $title, $parent_id)
	{
		$data = array(
			'type' => $type,
			'title' => $title,
			'parent_id' => intval($parent_id),
		);
		
		return $this->insert('category', $data);
	}

	public function delete_category($type, $category_id)
	{
		//递归删除子分类
		$childs = $this->get_category_list($type, false, intval($category_id));
		
		if($childs)
		{
			foreach($childs as $key => $val)
			{
				$this->delete_category($type, $val['id']);
			}
		}
		
		$this->delete('reputation_category', 'category_id = ' . intval($category_id));
		
		$this->delete('nav_menu', "type = 'category' AND type_id = " . intval($category_id));
		
		return $this->delete('category', 'id = ' . intval($category_id));
	}

	public function question_exists($category_id)
	{
		$question_count = $this->model('question')->count('question', 'category_id = ' . intval($category_id));
		
		return $question_count;
	}
	
	function check_url_token($url_token, $category_id)
	{
		return $this->count('category', "url_token = '" . $this->quote($url_token) . "' AND id != " . intval($category_id));
	}
}
