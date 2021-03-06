<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

use Icinga\Data\SimpleQuery;

/**
 * LDAP query class
 */
class LdapQuery extends SimpleQuery
{
    /**
     * The base dn being used for this query
     *
     * @var string
     */
    protected $base;

    /**
     * Whether this query is permitted to utilize paged results
     *
     * @var bool
     */
    protected $usePagedResults;

    /**
     * The name of the attribute used to unfold the result
     *
     * @var string
     */
    protected $unfoldAttribute;

    /**
     * This query's native LDAP filter
     *
     * @var string
     */
    protected $nativeFilter;

    /**
     * Initialize this query
     */
    protected function init()
    {
        $this->usePagedResults = false;
    }

    /**
     * Set the base dn to be used for this query
     *
     * @param   string  $base
     *
     * @return  $this
     */
    public function setBase($base)
    {
        $this->base = $base;
        return $this;
    }

    /**
     * Return the base dn being used for this query
     *
     * @return  string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Set whether this query is permitted to utilize paged results
     *
     * @param   bool    $state
     *
     * @return  $this
     */
    public function setUsePagedResults($state = true)
    {
        $this->usePagedResults = (bool) $state;
        return $this;
    }

    /**
     * Return whether this query is permitted to utilize paged results
     *
     * @return  bool
     */
    public function getUsePagedResults()
    {
        return $this->usePagedResults;
    }

    /**
     * Set the attribute to be used to unfold the result
     *
     * @param   string  $attributeName
     *
     * @return  $this
     */
    public function setUnfoldAttribute($attributeName)
    {
        $this->unfoldAttribute = $attributeName;
        return $this;
    }

    /**
     * Return the attribute to use to unfold the result
     *
     * @return  string
     */
    public function getUnfoldAttribute()
    {
        return $this->unfoldAttribute;
    }

    /**
     * Set this query's native LDAP filter
     *
     * @param   string  $filter
     *
     * @return  $this
     */
    public function setNativeFilter($filter)
    {
        $this->nativeFilter = $filter;
        return $this;
    }

    /**
     * Return this query's native LDAP filter
     *
     * @return  string
     */
    public function getNativeFilter()
    {
        return $this->nativeFilter;
    }

    /**
     * Choose an objectClass and the columns you are interested in
     *
     * {@inheritdoc} This creates an objectClass filter.
     */
    public function from($target, array $fields = null)
    {
        $this->where('objectClass', $target);
        return parent::from($target, $fields);
    }

    /**
     * Fetch result as tree
     *
     * @return  Root
     *
     * @todo    This is untested waste, not being used anywhere and ignores the query's order and base dn.
     *           Evaluate whether it's reasonable to properly implement and test it.
     */
    public function fetchTree()
    {
        $result = $this->fetchAll();
        $sorted = array();
        $quotedDn = preg_quote($this->ds->getDn(), '/');
        foreach ($result as $key => & $item) {
            $new_key = LdapUtils::implodeDN(
                array_reverse(
                    LdapUtils::explodeDN(
                        preg_replace('/,' . $quotedDn . '$/', '', $key)
                    )
                )
            );
            $sorted[$new_key] = $key;
        }
        unset($groups);
        ksort($sorted);

        $tree = Root::forConnection($this->ds);
        $root_dn = $tree->getDN();
        foreach ($sorted as $sort_key => & $key) {
            if ($key === $root_dn) {
                continue;
            }
            $tree->createChildByDN($key, $result[$key]);
        }
        return $tree;
    }

    /**
     * Fetch the distinguished name of the first result
     *
     * @return  string|false    The distinguished name or false in case it's not possible to fetch a result
     *
     * @throws  LdapException   In case the query returns multiple results
     *                          (i.e. it's not possible to fetch a unique DN)
     */
    public function fetchDn()
    {
        return $this->ds->fetchDn($this);
    }

    /**
     * Render and return this query's filter
     *
     * @return  string
     */
    public function renderFilter()
    {
        $filter = $this->ds->renderFilter($this->filter);
        if ($this->nativeFilter) {
            $filter = '(&(' . $this->nativeFilter . ')' . $filter . ')';
        }

        return $filter;
    }

    /**
     * Return the LDAP filter to be applied on this query
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->renderFilter();
    }
}
