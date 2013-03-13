<?php

class shopFrontendTagAction extends shopFrontendAction
{
    public function execute()
    {
        $tag = waRequest::param('tag');
        $this->setCollection(new shopProductsCollection('tag/'.waRequest::param('tag')));
        $this->setThemeTemplate('search.html');
        $this->view->assign('title', waRequest::param('tag'), true);
        $this->getResponse()->setTitle(_w('Tag').' - '.htmlspecialchars($tag));
    }

}