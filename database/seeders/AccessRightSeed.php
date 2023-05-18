<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessRightSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('access_rights')->delete();
        $access_rights = array(
            array('access_name' => 'Invitation Management', 'parent_access' => 'user management', 'access_description' => 'Users will be able to invite, update and remove invitations', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Role Profile', 'parent_access' => 'user management', 'access_description' => 'Users will be able to create | update | remove role profile, sign | revoke access right from profiles, add | remove and change user profiles', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'User Management', 'parent_access' => 'user management', 'access_description' => 'Users will be able to Enable and diable users, reset other users password', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Company Management', 'parent_access' => 'company management', 'access_description' => 'Users will be able to add, edit and remove company contacts, edit company information and address', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Group Management', 'parent_access' => 'company management', 'access_description' => 'Users will be able to add and edit groups, add and remove users from groups', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Channel Management', 'parent_access' => 'channel management', 'access_description' => 'Users will be able to add and remove DID\'s from company', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Inbound Calls', 'parent_access' => 'Calls', 'access_description' => 'Users will be able to answer incoming calls from their respective queues', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Outbound Calls', 'parent_access' => 'Calls', 'access_description' => 'Users will be able to make calls from their respective queues', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Click to Call', 'parent_access' => 'Calls', 'access_description' => 'Users will be able to make calls from their respective queues', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Customer Account Managers', 'parent_access' => 'CRM', 'access_description' => 'Users will be able to Can be able to create, edit and delete contacts in an account or approve edits that have been requested by team members, Can create and manage a customer account management form or contact information form, Can configure the Account number format,Can assign Customer Account management forms to Groups', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Sales Manager', 'parent_access' => 'CRM', 'access_description' => 'Users will be able to Can create a customer account , Can update a customer account and associated contacts, Can View a customer account and associated contact,	Approve Creation of customers by sales users ', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Sales Users', 'parent_access' => 'CRM', 'access_description' => 'Can create a customer account to be approved by Sales Managers, Can update a customer account and associated contacts, Can View a customer account and associated contacts  ', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Customer Service User', 'parent_access' => 'CRM', 'access_description' => 'Can create a customer account to be approved by Sales Managers, Can update a customer account and associated contacts, Can View a customer account and associated contacts  ', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Customer Account User', 'parent_access' => 'CRM', 'access_description' => 'Can create a customer account to be approved by Sales Managers, Can update a customer account and associated contacts, Can View a customer account and associated contacts  ', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Campaign Management', 'parent_access' => 'CRM', 'access_description' => 'Can create, update and run campaigns', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Chat Agent', 'parent_access' => 'Chat', 'access_description' => 'Can respond to cleint chat', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Chat Queue Manager', 'parent_access' => 'Chat', 'access_description' => 'Can create, update and assign agents to chat queues', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Chat Account Manager', 'parent_access' => 'Chat', 'access_description' => 'Can create, update and manage chat accounts', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Chat Flow Manager', 'parent_access' => 'Chat', 'access_description' => 'Can create, update and manage chat flows and bots', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Ticket Form Manager', 'parent_access' => 'Ticket', 'access_description' => 'Can create, update and manage ticket form', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Ticket Escalation Manager', 'parent_access' => 'Ticket', 'access_description' => 'Can create, update and manage ticket escalations and SLAs', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Help Desk Manager', 'parent_access' => 'Ticket', 'access_description' => 'Can create, update and manage Help desks', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Ticket User', 'parent_access' => 'Ticket', 'access_description' => 'Can resolve, pend and escalate tickets assigned to them', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Ticket Create User', 'parent_access' => 'Ticket', 'access_description' => 'Can create tickets', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'QA Form Manager', 'parent_access' => 'Quality Assurance', 'access_description' => 'Can create, update and manage QA forms', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'QA Team Manager', 'parent_access' => 'Quality Assurance', 'access_description' => 'Can create, update, and manage QA teams', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'IVR Manager', 'parent_access' => 'IVR', 'access_description' => 'Can create, update, and manage IVRs', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'MOH Manager', 'parent_access' => 'MOH', 'access_description' => 'Can create, update, and manage MOHs', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'MOH Queue Manager', 'parent_access' => 'MOH', 'access_description' => 'Can add and remove MOH to Queues', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'Queue Agent Management', 'parent_access' => 'Queue', 'access_description' => 'Can add and remove agents to Queues', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'knowledge Base Create', 'parent_access' => 'Knowledge Base', 'access_description' => 'Can add, update and remove Knowledge base records', 'created_at' => now(), 'updated_at' => now()),
            array('access_name' => 'knowledge Base Approve', 'parent_access' => 'Knowledge Base', 'access_description' => 'Can add, update, remove and approve Knowledge base records', 'created_at' => now(), 'updated_at' => now()),

        );

        DB::table('access_rights')->upsert($access_rights,'access_name');
    }
}
