// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript helper function for Folder module
 *
 * @package    mod
 * @subpackage gotomeeting
 * @copyright  2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_gotomeeting = {};

M.mod_gotomeeting.attendance = function (Y,data) {
    console.log(data);

    Y.use("datatable-sort", function (Y) {
        var cols = [
            {key: "Company", label: "Click to Sort Column A", sortable: true},
            {key: "Phone", label: "Not Sortable Column B"},
            {key: "Contact", label: "Click to Sort Column C", sortable: true}
        ],
                data1 = [
                    {Company: "Company Bee", Phone: "415-555-1234", Contact: "Sally Spencer"},
                    {Company: "Acme Company", Phone: "650-555-4444", Contact: "John Jones"},
                    {Company: "Industrial Industries", Phone: "408-555-5678", Contact: "Robin Smith"}
                ],
                table = new Y.DataTable({
                    columns: data.header,
                    data: data.data,
                    summary: "",
                    caption: ""
                }).render("#attendance");
    });
  
}