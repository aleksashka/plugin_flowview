# flowview

This plugin allows you to see reports based off the data in your Netflow flows.

# Features

Fully customizable reports

# Installation

Required:

First, make sure you have the plugin architecture installed.

Then, Install just like any other plugin, just copy it into the plugin directory,
and Use Console->Plugin Mangement to Install and Enable.

Then, you must install flow-tools.  Generally, flow-tools is available through yum, 
apt-get, emerge, etc.  Locate the path for your flow-tools (typically /usr/bin), and
decide on a location for your flow data (in many cases /var/flow, or any other location).

Once you have done all of this, goto Console->Settings->Misc and setup this information
in the FlowView section.

Next you have to setup your Cacti server as a FlowView sink from your various
sources.  Then, from FlowView -> Listeners, you must add the various listeners for all
your flow-capture sources.

Optional:

If you don't already have flows coming to the cacti box and being stored with flow-capture
you may follow the steps below to have cacti do this.

Next copy the 'flow-capture' file in the flowview plugin directory to '/etc/init.d/'
or the appropriate location.  You have to edit this file to set the cacti base path.
This file also supercedes whatever the operating system installed.

Finally, start the 'flow-capture' process using '/etc/init.d/flow-capture start'.

You may also want to insure that 'flow-capture' is a startup service using your operating
systems default process.

Also, remember that when using the Cacti 'flow-capture' init binary, that when you make
changes to the various listeners, you must subsequently 'restart' the 'flow-capture' binary.

# Possible Bugs?

If you figure out this problem, let me know!!!

# Future Changes

Got any ideas or complaints, please e-mail me!

# Changelog
	--- 2.0 ---
	feature: Support for Cacti 1.0
	feature: Support for Ugroup Plugin
	feature: Use either the OS' DNS or Alternate
	feature: Add strip domain capabilities
	bug: Not supporting Protocols correctly and Prefix/Suffix
	bug: Some W3C Validation Changes
	bug: Table plugin_flowview_devices wrong engine

	--- 1.1 ---
	bug: FlowView Settings were hidden for some reason
	bug: flow-capture script incomplete

	--- 1.0 ---
	compat: Making compatible with 0.8.7g
	feature: Allow sending emails on demand
	feature: Add SaveAs, Delete, Update to UI
	feature: Add a Veiwer Only Permission Level
	feature: Add a Title for Scheduled Reports
	feature; Re-tool many reports into pure HTML
	feature: Add Graphs for Flows, Bytes, and Packets
	feature: Support sortable tables
	feature: Support excluding outliers from report
	bug: Rename 'View' tab to 'Filter'
	bug: Rename 'Devices' to 'Listeners'

	--- 0.6 ---
	compat: Now only PA 2.0 compatible
	bug: Fix for IE and saving Queries
	bug: Fix for Error when no devices

	--- 0.5 ---
	feature: Add flow-tools replacement startup script to allow launching of multiple processes based upon devices added
	feature: Add Saved Queries
	feature: Change Sort field to be drop downs with column names
	feature: Add ability to schedule and email out Netflow Scans
	bug: Fix issue with start and stop times close to midnight not loading the proper days data

	--- 0.4 ---
	bug: Minor fix for when using flow path "/"
	bug: Fix Cacti 0.8.7 Compatibility

	--- 0.3 ---
	feature: Add time support for relative times (NOW, -1 HOUR, -2 DAYS, -10 MINTUES) Must leave Date blank for these to work properly
	feature: Add device name to path if present

	--- 0.2 ---
	feature: Add DNS Support

	--- 0.1 ---
	Initial release

