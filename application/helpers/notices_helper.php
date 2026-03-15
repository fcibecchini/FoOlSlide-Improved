<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!function_exists('get_notices'))
{
	function get_notice_items()
	{
		$CI = & get_instance();
		$flashdata = array();
		if (isset($CI->session))
		{
			$flashdata = $CI->session->flashdata('notices');
			if (!is_array($flashdata))
			{
				$flashdata = array();
			}
		}

		$flash_notice_data = is_array($CI->flash_notice_data) ? $CI->flash_notice_data : array();
		$notices = isset($CI->notices) && is_array($CI->notices) ? $CI->notices : array();
		$merge = array_merge($notices, $flash_notice_data, $flashdata);
		$CI->flash_notice_data = '';

		return $merge;
	}

	/*
	 * Returns the notices with the Twitter Bootstrap notices formatting, and unsets
	 * the array lines from the flash
	 * 
	 * @author Woxxy
	 */
	function get_notices()
	{
		$merge = get_notice_items();
		$echo = '';
		foreach ($merge as $key => $value)
		{
			$echo .= '<div class="alert-message ' . $value["type"] . ' fade in" data-alert="alert"><a class="close" href="#">&times;</a><p>' . $value["message"] . '</p></div>';
		}
		return $echo;
	}


}

if (!function_exists('get_notice_toasts'))
{
	function get_notice_toasts($theme = 'default', $position = 'floating')
	{
		$items = get_notice_items();
		if (empty($items))
		{
			return '';
		}

		$styles = array(
			'default' => array(
				'text' => '#000',
				'muted' => '#222',
				'success_bg' => '#f2fff0',
				'success_border' => '#6aa84f',
				'error_bg' => '#fff3f3',
				'error_border' => '#cc4125',
				'warning_bg' => '#fff9e8',
				'warning_border' => '#bf9000',
				'shadow' => '0 8px 24px rgba(0,0,0,.18)',
			),
			'dazen-skin' => array(
				'text' => '#d7dce5',
				'muted' => '#f0f0f0',
				'success_bg' => '#1f3a2d',
				'success_border' => '#5da271',
				'error_bg' => '#3a2027',
				'error_border' => '#c96b7a',
				'warning_bg' => '#3a321f',
				'warning_border' => '#d3ac53',
				'shadow' => '0 10px 28px rgba(0,0,0,.35)',
			),
		);

		$palette = isset($styles[$theme]) ? $styles[$theme] : $styles['default'];
		$stack_style = 'display: flex; flex-direction: column; gap: 10px; width: min(28rem, calc(100vw - 36px));';
		if ($position === 'inline')
		{
			$stack_style = 'display: flex; flex-direction: column; gap: 10px; width: min(100%, 42rem); margin-top: 14px;';
		}
		else
		{
			$stack_style = 'position: fixed; top: 18px; right: 18px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; width: min(28rem, calc(100vw - 36px));';
		}

		$echo = '<div class="fs-toast-stack" data-fs-toast-stack="1" style="' . $stack_style . '">';

		foreach ($items as $index => $item)
		{
			$type = isset($item['type']) ? $item['type'] : 'success';
			$message = isset($item['message']) ? $item['message'] : '';
			$background = $palette['success_bg'];
			$border = $palette['success_border'];
			if ($type === 'error')
			{
				$background = $palette['error_bg'];
				$border = $palette['error_border'];
			}
			elseif ($type === 'warning')
			{
				$background = $palette['warning_bg'];
				$border = $palette['warning_border'];
			}

			$echo .= '<div data-fs-toast="1" style="background: ' . $background . '; border: 1px solid ' . $border . '; border-left-width: 6px; border-radius: 12px; box-shadow: ' . $palette['shadow'] . '; color: ' . $palette['text'] . '; padding: 14px 16px; font: inherit; line-height: 1.5; opacity: 0; transform: translateY(-8px); transition: opacity .25s ease, transform .25s ease;">';
			$heading = _('Success');
			if ($type === 'error')
			{
				$heading = _('Error');
			}
			elseif ($type === 'warning')
			{
				$heading = _('Warning');
			}

			$echo .= '<div style="color: ' . $palette['muted'] . '; font-weight: 700; margin-bottom: 4px;">' . htmlspecialchars($heading) . '</div>';
			$echo .= '<div style="color: ' . $palette['text'] . ';">' . htmlspecialchars($message) . '</div>';
			$echo .= '</div>';
		}

		$echo .= '</div>';
		$echo .= '<script>(function(){var stack=document.querySelector("[data-fs-toast-stack=\'1\']");if(!stack){return;}var toasts=stack.querySelectorAll("[data-fs-toast=\'1\']");for(var i=0;i<toasts.length;i++){(function(toast,index){setTimeout(function(){toast.style.opacity="1";toast.style.transform="translateY(0)";},20+(index*80));setTimeout(function(){toast.style.opacity="0";toast.style.transform="translateY(-8px)";setTimeout(function(){if(toast.parentNode){toast.parentNode.removeChild(toast);}if(stack && !stack.querySelector("[data-fs-toast=\'1\']")){stack.parentNode.removeChild(stack);}},260);},4200+(index*180));})(toasts[i],i);}})();</script>';

		return $echo;
	}
}

if (!function_exists('clear_notices'))
{
	/*
	 * Flushes flashdata and standard notices
	 * 
	 * @author Woxxy
	 */
	function clear_notices()
	{
		$CI = & get_instance();
		unset($CI->notices);
		$CI->flash_notice_data = array();
	}


}

if (!function_exists('set_notice'))
{
	/*
	 * Sets a notice in the currently loading page. Can be used for multiple notices
	 * Notice types: error, warn, notice
	 * 
	 * @author Woxxy
	 */
	function set_notice($type, $message, $data = FALSE)
	{
		if ($type == 'warn')
			$type = 'warning';
		if ($type == 'notice')
			$type = 'success';

		$CI = & get_instance();
		$CI->notices[] = array("type" => $type, "message" => $message, "data" => $data);

		if ($CI->input->is_cli_request())
		{
			echo '[' . $type . '] ' . $message . PHP_EOL;
		}
	}


}

if (!function_exists('flash_notice'))
{
	/*
	 * Sets a notice in the next loaded page. Can be used for multiple notices
	 * Notice types: error, warn, notice
	 * 
	 * @author Woxxy
	 */
	function flash_notice($type, $message)
	{
		if ($type == 'warn')
			$type = 'warning';
		if ($type == 'notice')
			$type = 'success';

		$CI = & get_instance();
		$CI->flash_notice_data[] = array('type' => $type, 'message' => $message);
		$CI->session->set_flashdata('notices', $CI->flash_notice_data);
	}


}
