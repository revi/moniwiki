<?php
// Copyright 2003 by Won-Kyu Park <wkpark at kldp.org>
// All rights reserved. Distributable under GPL see COPYING
// a Attachment macro plugin for the MoniWiki
//
// Usage: [[Attachment(filename)]]
//
// $Id$

function macro_Attachment($formatter,$value,$option='') {
  global $DBInfo;

  $attr='';
  if ($DBInfo->force_download) $force_download=1;
  if ($DBInfo->download_action) $mydownload=$DBInfo->download_action;
  else $mydownload='download';
  $extra_action='';

  $text='';

  if (($p=strpos($value,' ')) !== false) {
    // [attachment:my.ext hello]
    // [attachment:my.ext attachment:my.png]
    // [attachment:my.ext http://url/../my.png]
    if ($value[0]=='"' and ($p2=strpos(substr($value,1),'"')) !== false) {
      $text=$ntext=substr($value,$p2+3);
      $dummy=substr($value,1,$p2); # "my image.png" => my image.png
      $args=substr($value,$p2+2);
      $value=$dummy.$args; # append query string
    } else {
      $text=$ntext=substr($value,$p+1);
      $value=substr($value,0,$p);
    }
    if (substr($text,0,11)=='attachment:') {
      $fname=substr($text,11);
      $ntext=macro_Attachment($formatter,$fname,1);
    }
    if (preg_match("/\.(png|gif|jpeg|jpg)$/i",$ntext)) {
      if (!file_exists($ntext)) {
        $fname=preg_replace('/^"([^"]*)"$/',"\\1",$fname);
        $mydownload='UploadFile&amp;rename='.$fname;
        $text=sprintf(_("Upload new Attachment \"%s\""),$fname);
        $text=str_replace('"','\'',$text);
      }
      $ntext=qualifiedUrl($DBInfo->url_prefix.'/'.$ntext);
      $img_link='<img src="'.$ntext.'" alt="'.$text.'" border="0" />';
    }
  } else {
    $value=str_replace('%20',' ',$value);
  }

  if (($dummy=strpos($value,'?'))) {
    # for attachment: syntax
    parse_str(substr($value,$dummy+1),$attrs);
    $value=substr($value,0,$dummy);
    foreach ($attrs as $name=>$val) {
      if ($name=='action') {
        if ($val == 'deletefile') $extra_action=$val;
        else $mydownload=$val;
      } else
        $attr.="$name=\"$val\" ";
    }

    if ($attrs['align']) $attr.='class="img'.ucfirst($attrs['align']).'" ';
  } else if (($dummy=strpos($value,','))) {
    # for Attachment macro
    $args=explode(',',substr($value,$dummy+1));
    $value=substr($value,0,$dummy);
    foreach ($args as $arg)
      $attr.="$arg ";
  }

  if (($p=strpos($value,':')) !== false or ($p=strpos($value,'/')) !== false) {
    $subpage=substr($value,0,$p);
    $file=substr($value,$p+1);
    $value=$subpage.'/'.$file; # normalize page arg
    if ($subpage and is_dir($DBInfo->upload_dir.'/'.$DBInfo->pageToKeyname($subpage))) {
      $pagename=$subpage;
      $key=$DBInfo->pageToKeyname($subpage);
    } else {
      $pagename='';
      $key='';
    }
    $dir=$key ? $DBInfo->upload_dir.'/'.$key:$DBInfo->upload_dir;
  } else {
    $pagename=$formatter->page->name;
    $key=$DBInfo->pageToKeyname($formatter->page->name);
    $dir=$DBInfo->upload_dir.'/'.$key;
    $file=$value;
  }
  // check file name XXX
  if (!$file) return 'attachment:/';

  $upload_file=$dir.'/'.$file;
  if ($option == 1) return $upload_file;
  if (!$text) $text=$file;

  if (file_exists($upload_file)) {
    if (!$img_link && preg_match("/\.(png|gif|jpeg|jpg)$/i",$upload_file)) {
      if ($key != $pagename || $force_download)
        $url=$formatter->link_url(_urlencode($pagename),"?action=$mydownload&amp;value=".urlencode($value));
      else
        $url=$DBInfo->url_prefix."/"._urlencode($upload_file);
      $img="<img src='$url' alt='$file' $attr/>";

      if ($extra_action) {
        $url=$formatter->link_url(_urlencode($pagename),"?action=$extra_action&amp;value=".urlencode($value));
        $img="<a href='$url'>$img</a>";
      }
      
      return "<span class=\"imgAttach\">$img</span>";
    } else {
      $mydownload= $extra_action ? $extra_action:$mydownload;
      $link=$formatter->link_url(_urlencode($pagename),"?action=$mydownload&amp;value=".urlencode($value),$text);
      if ($img_link)
        return "<span class=\"attach\"><a href='$link'>$img_link</a></span>";

      return "<span class=\"attach\"><img align='middle' src='$DBInfo->imgs_dir_interwiki".'uploads-16.png\' /><a href="'.$link.'">'.$text.'</a></span>';
    }
  }

  $paste='';
  if ($DBInfo->use_clipmacro and preg_match('/^(.*)\.png$/i',$file,$m)) {
    $now=time();
    $url=$formatter->link_url($pagename,"?action=clip&amp;value=$m[1]&amp;now=$now");
    $paste=" <a href='$url'>"._("or paste a new picture")."</a>";
  }
  if ($pagename == $formatter->page->name)
    return '<span class="attach">'.$formatter->link_to("?action=UploadFile&amp;rename=".urlencode($file),sprintf(_("Upload new Attachment \"%s\""),$file)).$paste.'</span>';

  if (!$pagename) $pagename='UploadFile';
  return '<span class="attach">'.$formatter->link_tag($pagename,"?action=UploadFile&amp;rename=".urlencode($file),sprintf(_("Upload new Attachment \"%s\" on the \"%s\""),$file, $pagename)).$paste.'</span>';
}

// vim:et:sts=2:
?>
