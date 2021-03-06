<?php
/**
 ***********************************************************************************************
 * @copyright 2004-2016 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * @class ModuleDates
 * @brief This class reads date recordsets from database
 *
 * This class reads all available recordsets from table dates.
 * and returns an Array with results, recordsets and validated parameters from $_GET Array.
 * @par Returned Array
 * @code
 * array(
 *         [numResults] => 1
 *         [limit] => 10
 *         [totalCount] => 1
 *         [recordset] => Array
 *         (
 *             [0] => Array
 *                 (
 *                     [0] => 10
 *                     [cat_id] => 10
 *                     [1] => 1
 *                     [cat_org_id] => 1
 *                     [2] => DAT
 *                     [cat_type] => DAT
 *                     [3] => COMMON
 *                     [cat_name_intern] => COMMON
 *                     [4] => Allgemein
 *                     [cat_name] => Allgemein
 *                     [5] => 0
 *                     [cat_hidden] => 0
 *                     [6] => 0
 *                     [cat_system] => 0
 *                     [7] => 0
 *                     [cat_default] => 0
 *                     [8] => 1
 *                     [cat_sequence] => 1
 *                     [9] => 1
 *                     [cat_usr_id_create] => 1
 *                     [10] => 2012-01-08 11:12:05
 *                     [cat_timestamp_create] => 2012-01-08 11:12:05
 *                     [11] =>
 *                     [cat_usr_id_change] =>
 *                     [12] =>
 *                     [cat_timestamp_change] =>
 *                     [13] => 9
 *                     [dat_id] => 9
 *                     [14] => 10
 *                     [dat_cat_id] => 10
 *                     [15] =>
 *                     [dat_rol_id] =>
 *                     [16] =>
 *                     [dat_room_id] =>
 *                     [17] => 0
 *                     [dat_global] => 0
 *                     [18] => 2013-09-21 21:00:00
 *                     [dat_begin] => 2013-09-21 21:00:00
 *                     [19] => 2013-09-21 22:00:00
 *                     [dat_end] => 2013-09-21 22:00:00
 *                     [20] => 0
 *                     [dat_all_day] => 0
 *                     [21] => 0
 *                     [dat_highlight] => 0
 *                     [22] =>
 *                     [dat_description] =>
 *                     [23] =>
 *                     [dat_location] =>
 *                     [24] =>
 *                     [dat_country] =>
 *                     [25] => eet
 *                     [dat_headline] => eet
 *                     [26] => 0
 *                     [dat_max_members] => 0
 *                     [27] => 1
 *                     [dat_usr_id_create] => 1
 *                     [28] => 2013-09-20 21:56:23
 *                     [dat_timestamp_create] => 2013-09-20 21:56:23
 *                     [29] =>
 *                     [dat_usr_id_change] =>
 *                     [30] =>
 *                     [dat_timestamp_change] =>
 *                     [31] =>
 *                     [member_date_role] =>
 *                     [32] =>
 *                     [mem_leader] =>
 *                     [33] => Paul Webmaster
 *                     [create_name] => Paul Webmaster
 *                     [34] =>
 *                     [change_name] =>
 *                 )
 *
 *         )
 *
 *     [parameter] => Array
 *         (
 *             [active_role] => 1
 *             [calendar-selection] => 1
 *             [cat_id] => 0
 *             [category-selection] => 0,
 *             [date] =>
 *             [daterange] => Array
 *                 (
 *                     [english] => Array
 *                         (
 *                             [start_date] => 2013-09-21
 *                             [end_date] => 9999-12-31
 *                         )
 *
 *                     [system] => Array
 *                         (
 *                             [start_date] => 21.09.2013
 *                             [end_date] => 31.12.9999
 *                         )
 *
 *                 )
 *
 *             [headline] => Termine
 *             [id] => 0
 *             [mode] => actual
 *             [order] => ASC
 *             [startelement] => 0
 *             [view_mode] => html
 *         )
 *
 * )
 * @endcode
 */
class ModuleDates extends Modules
{
    /**
     * Constructor that will create an object of a parameter set needed in modules to get the recordsets.
     * Initialize parameters
     */
    public function __construct()
    {
        parent::__construct();

        $this->setParameter('mode', 'actual');
    }

    /**
     * SQL query returns an array with available dates.
     * @param int $startElement Defines the offset of the query (default: 0)
     * @param int $limit        Limit of query rows (default: 0)
     * @return array Array with all results, dates and parameters.
     */
    public function getDataSet($startElement = 0, $limit = null)
    {
        global $gDb, $gPreferences, $gCurrentUser, $gCurrentOrganization;

        if ($limit === null)
        {
            $limit = (int) $gPreferences['dates_per_page'];
        }

        if ($gPreferences['system_show_create_edit'] == 1)
        {
            // show firstname and lastname of create and last change user
            $additionalFields = '
                cre_firstname.usd_value || \' \' || cre_surname.usd_value AS create_name,
                cha_firstname.usd_value || \' \' || cha_surname.usd_value AS change_name ';
        }
        else
        {
            // show username of create and last change user
            $additionalFields = ' cre_username.usr_login_name AS create_name,
                                  cha_username.usr_login_name AS change_name ';
        }

        // read dates from database
        $sql = 'SELECT DISTINCT cat.*, dat.*, mem.mem_usr_id AS member_date_role, mem.mem_leader,' . $additionalFields . '
                  FROM ' . TBL_DATE_ROLE . ' dtr
            INNER JOIN ' . TBL_DATES . ' dat
                    ON dat_id = dtr_dat_id
            INNER JOIN ' . TBL_CATEGORIES . ' cat
                    ON cat_id = dat_cat_id
                       ' . $this->sqlAdditionalTablesGet('data') . '
             LEFT JOIN ' . TBL_MEMBERS . ' mem
                    ON mem.mem_rol_id = dat_rol_id
                   AND mem.mem_usr_id = ' . $gCurrentUser->getValue('usr_id') . '
                   AND mem.mem_begin <= \'' . DATE_NOW . '\'
                   AND mem.mem_end    > \'' . DATE_NOW . '\'
                 WHERE (  cat_org_id = ' . $gCurrentOrganization->getValue('org_id') . '
                       OR  (   dat_global = 1
                           AND cat_org_id IN (' . $gCurrentOrganization->getFamilySQL() . ')
                           )
                       )
                       ' . $this->sqlConditionsGet() . '
                       ORDER BY dat_begin ' . $this->order;

        // Parameter
        if ($limit > 0)
        {
            $sql .= ' LIMIT ' . $limit;
        }
        if ($startElement > 0)
        {
            $sql .= ' OFFSET ' . $startElement;
        }

        $datesStatement = $gDb->query($sql);

        // array for results
        return array(
            'recordset'  => $datesStatement->fetchAll(),
            'numResults' => $datesStatement->rowCount(),
            'limit'      => $limit,
            'totalCount' => $this->getDataSetCount()
        );
    }

    /**
     * Returns a module specific headline
     * @param string $headline The initial headline of the module.
     * @return string Returns the full headline of the module
     */
    public function getHeadline($headline)
    {
        global $gDb, $gL10n, $gCurrentOrganization;

        // set headline with category name
        if ($this->getParameter('cat_id') > 0)
        {
            $category  = new TableCategory($gDb, $this->getParameter('cat_id'));
            $headline .= ' - ' . $category->getValue('cat_name');
        }

        // check time period if old dates are chosen, then set headline to previous dates
        // Define a prefix
        if ($this->getParameter('mode') === 'old'
        ||    ($this->getParameter('dateStartFormatEnglish') < DATE_NOW
            && $this->getParameter('dateEndFormatEnglish')   < DATE_NOW))
        {
            $headline = $gL10n->get('DAT_PREVIOUS_DATES', '') . $headline;
        }

        if ($this->getParameter('view_mode') === 'print')
        {
            $headline = $gCurrentOrganization->getValue('org_longname') . ' - ' . $headline;
        }

        return $headline;
    }

    /**
     * Get number of available dates.
     * @return int
     */
    public function getDataSetCount()
    {
        if ($this->id === 0)
        {
            global $gDb, $gCurrentOrganization;

            $sql = 'SELECT COUNT(DISTINCT dat_id) AS count
                      FROM ' . TBL_DATE_ROLE . '
                INNER JOIN ' . TBL_DATES . '
                        ON dat_id = dtr_dat_id
                INNER JOIN ' . TBL_CATEGORIES . '
                        ON cat_id = dat_cat_id
                           ' . $this->sqlAdditionalTablesGet('count') . '
                     WHERE ( cat_org_id = ' . $gCurrentOrganization->getValue('org_id') . '
                           OR  (   dat_global = 1
                               AND cat_org_id IN (' . $gCurrentOrganization->getFamilySQL() . ')
                               )
                           )'
                           . $this->sqlConditionsGet();

            $statement = $gDb->query($sql);

            return (int) $statement->fetchColumn();
        }

        return 1;
    }

    /**
     * Set a date range in which the dates should be searched. The method will fill
     * 4 parameters @b dateStartFormatEnglish, @b dateStartFormatEnglish,
     * @b dateEndFormatEnglish and @b dateEndFormatAdmidio that could be read with
     * getParameter and could be used in the script.
     * @param string $dateRangeStart A date in english or Admidio format that will be the start date of the range.
     * @param string $dateRangeEnd   A date in english or Admidio format that will be the end date of the range.
     * @throws AdmException SYS_DATE_END_BEFORE_BEGIN
     * @return bool Returns false if invalid date format is submitted
     */
    public function setDateRange($dateRangeStart = '', $dateRangeEnd = '')
    {
        global $gPreferences;

        if ($dateRangeStart === '')
        {
            $dateStart = '1970-01-01';
            $dateEnd   = (date('Y') + 10) . '-12-31';

            // set date_from and date_to regarding to current mode
            switch ($this->mode)
            {
                case 'actual':
                    $dateRangeStart = DATE_NOW;
                    $dateRangeEnd   = $dateEnd;
                    break;
                case 'old':
                    $dateRangeStart = $dateStart;
                    $dateRangeEnd   = DATE_NOW;
                    break;
                case 'all':
                    $dateRangeStart = $dateStart;
                    $dateRangeEnd   = $dateEnd;
                    break;
            }
        }
        // If mode=old then we want to have the events in reverse order ('DESC')
        if ($this->mode === 'old')
        {
            $this->order = 'DESC';
        }

        // Create date object and format date_from in English format and system format and push to daterange array
        $objDateFrom = DateTime::createFromFormat('Y-m-d', $dateRangeStart);

        if ($objDateFrom === false)
        {
            // check if date_from has system format
            $objDateFrom = DateTime::createFromFormat($gPreferences['system_date'], $dateRangeStart);
        }

        if ($objDateFrom === false)
        {
            return false;
        }

        $this->setParameter('dateStartFormatEnglish', $objDateFrom->format('Y-m-d'));
        $this->setParameter('dateStartFormatAdmidio', $objDateFrom->format($gPreferences['system_date']));

        // Create date object and format date_to in English format and system format and push to daterange array
        $objDateTo = DateTime::createFromFormat('Y-m-d', $dateRangeEnd);

        if ($objDateTo === false)
        {
            // check if date_from  has system format
            $objDateTo = DateTime::createFromFormat($gPreferences['system_date'], $dateRangeEnd);
        }

        if ($objDateTo === false)
        {
            return false;
        }

        $this->setParameter('dateEndFormatEnglish', $objDateTo->format('Y-m-d'));
        $this->setParameter('dateEndFormatAdmidio', $objDateTo->format($gPreferences['system_date']));

        // DateTo should be greater than DateFrom (Timestamp must be less)
        if ($objDateFrom->getTimestamp() > $objDateTo->getTimestamp())
        {
            throw new AdmException('SYS_DATE_END_BEFORE_BEGIN');
        }

        return true;
    }

    /**
     * Get additional tables for sql statement
     * @param string $type of sql statement:
     *                     data:  is joining tables to get more data from them
     *                     count: is joining tables only to get the correct number of records (default: 'data')
     * @return string String with the necessary joins
     */
    public function sqlAdditionalTablesGet($type = 'data')
    {
        global $gPreferences, $gProfileFields;

        $additionalTables = '';

        if ($type === 'data')
        {
            if ($gPreferences['system_show_create_edit'] == 1)
            {
                // Tables for showing firstname and lastname of create and last change user
                $additionalTables = '
                    LEFT JOIN ' . TBL_USER_DATA . ' cre_surname
                           ON cre_surname.usd_usr_id = dat_usr_id_create
                          AND cre_surname.usd_usf_id = ' . $gProfileFields->getProperty('LAST_NAME', 'usf_id') . '
                    LEFT JOIN ' . TBL_USER_DATA . ' cre_firstname
                           ON cre_firstname.usd_usr_id = dat_usr_id_create
                          AND cre_firstname.usd_usf_id = ' . $gProfileFields->getProperty('FIRST_NAME', 'usf_id') . '
                    LEFT JOIN ' . TBL_USER_DATA . ' cha_surname
                           ON cha_surname.usd_usr_id = dat_usr_id_change
                          AND cha_surname.usd_usf_id = ' . $gProfileFields->getProperty('LAST_NAME', 'usf_id') . '
                    LEFT JOIN ' . TBL_USER_DATA . ' cha_firstname
                           ON cha_firstname.usd_usr_id = dat_usr_id_change
                          AND cha_firstname.usd_usf_id = ' . $gProfileFields->getProperty('FIRST_NAME', 'usf_id');
            }
            else
            {
                // Tables for showing username of create and last change user
                $additionalTables = '
                    LEFT JOIN '. TBL_USERS .' cre_username
                           ON cre_username.usr_id = dat_usr_id_create
                    LEFT JOIN '. TBL_USERS .' cha_username
                           ON cha_username.usr_id = dat_usr_id_change ';
            }
        }

        return $additionalTables;
    }

    /**
     * Prepare SQL Statement.
     * @return string
     */
    private function sqlConditionsGet()
    {
        global $gValidLogin, $gCurrentUser;

        $sqlConditions = '';

        // if user isn't logged in, then don't show hidden categories
        if (!$gValidLogin)
        {
            $sqlConditions .= ' AND cat_hidden = 0 ';
        }

        $id = $this->getParameter('id');
        // In case ID was permitted and user has rights
        if ($id > 0)
        {
            $sqlConditions .= ' AND dat_id = ' . $id;
        }
        // ...otherwise get all additional events for a group
        else
        {
            if (!$this->getParameter('dateStartFormatEnglish'))
            {
                $this->setDateRange(); // TODO Exception handling
            }

            // add 1 second to end date because full time events to until next day
            $sqlConditions .= ' AND dat_begin <= \'' . $this->getParameter('dateEndFormatEnglish')   . ' 23:59:59\'
                                AND dat_end   >  \'' . $this->getParameter('dateStartFormatEnglish') . ' 00:00:00\' ';

            // show all events from category
            if ($this->getParameter('cat_id') > 0)
            {
                // show all events from category
                $sqlConditions .= ' AND cat_id = ' . $this->getParameter('cat_id');
            }
        }

        $usrId = (int) $gCurrentUser->getValue('usr_id');
        // add conditions for role permission
        if ($usrId > 0)
        {
            $subSelect = '(SELECT mem_rol_id
                             FROM ' . TBL_MEMBERS . ' mem2
                            WHERE mem2.mem_usr_id = ' . $usrId . '
                              AND mem2.mem_begin <= dat_begin
                              AND mem2.mem_end   >= dat_end)';
            switch ($this->getParameter('show'))
            {
                case 'all':
                    $sqlConditions .= '
                        AND (  dtr_rol_id IS NULL
                            OR dtr_rol_id IN ' . $subSelect . ' ) ';
                    break;
                case 'maybe_participate':
                    $sqlConditions .= '
                        AND dat_rol_id IS NOT NULL
                        AND (  dtr_rol_id IS NULL
                            OR dtr_rol_id IN ' . $subSelect . ' ) ';
                    break;
                case 'only_participate':
                    $sqlConditions .= '
                        AND dat_rol_id IS NOT NULL
                        AND dat_rol_id IN ' . $subSelect;
                    break;
            }
        }
        else
        {
            $sqlConditions .= ' AND dtr_rol_id IS NULL ';
        }

        return $sqlConditions;
    }

    /**
     * Method validates all date inputs and formats them to date format 'Y-m-d' needed for database queries
     * @deprecated 3.2.0:4.0.0 Dropped without replacement.
     * @param string $date Date to be validated and formated if needed
     * @return string|false
     */
    private function formatDate($date)
    {
        global $gLogger, $gPreferences;

        $gLogger->warning('DEPRECATED: "$moduleDates->formatDate()" is deprecated without replacement!');

        $objDate = DateTime::createFromFormat('Y-m-d', $date);
        if ($objDate !== false)
        {
            return $date;
        }

        // check if date has system format
        $objDate = DateTime::createFromFormat($gPreferences['system_date'], $date);
        if ($objDate !== false)
        {
            return $objDate->format('Y-m-d');
        }

        return false;
    }

    /**
     * Returns value for form field.
     * This method compares a date value to a reference value and to date '1970-01-01'.
     * Html output will be set regarding the parameters.
     * If value matches the reference or date('1970-01-01'), the output value is cleared to get an empty string.
     * This method can be used to fill a html form
     * @deprecated 3.2.0:4.0.0 Dropped without replacement.
     * @param string $date      Date is to be checked to reference and default date '1970-01-01'.
     * @param string $reference Reference date
     * @return string|false String with date value, or an empty string, if $date is '1970-01-01' or reference date
     */
    public function getFormValue($date, $reference)
    {
        global $gLogger;

        $gLogger->warning('DEPRECATED: "$moduleDates->getFormValue()" is deprecated without replacement!');

        if (isset($date, $reference))
        {
            return $this->setFormValue($date, $reference);
        }

        return false;
    }

    /**
     * Check date value to reference and set html output.
     * If value matches to reference, value is cleared to get an empty string.
     * @deprecated 3.2.0:4.0.0 Dropped without replacement.
     * @param string $date
     * @param string $reference
     * @return string
     */
    private function setFormValue($date, $reference)
    {
        global $gLogger;

        $gLogger->warning('DEPRECATED: "$moduleDates->setFormValue()" is deprecated without replacement!');

        $checkedDate = $this->formatDate($date);
        if ($checkedDate === $reference || $checkedDate === '1970-01-01')
        {
            $date = '';
        }

        return $date;
    }
}
