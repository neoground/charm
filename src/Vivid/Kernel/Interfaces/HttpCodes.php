<?php
/**
 * This file contains the HttpCodes interface
 */

namespace Charm\Vivid\Kernel\Interfaces;

/**
 * Trait HttpCodes
 *
 * Providing most common HTTP status codes as constants
 *
 * Descriptions by RFC2610 Section 10: https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
 *
 * @package Charm\Vivid\Kernel\Traits
 */
interface HttpCodes
{
    // ----------------------------------
    // 2XX - Success
    // ----------------------------------

    /**
     * @var int The request has succeeded.
     */
    const HTTP_OK = 200;

    /**
     * @var int The request has been fulfilled and resulted in a new resource being created.
     */
    const HTTP_Created = 201;

    /**
     * @var int The request has been accepted for processing, but the processing has not been completed.
     */
    const HTTP_Accepted = 202;

    /**
     * @var int he server has fulfilled the request but does not need to return an entity-body,
     * and might want to return updated metainformation.
     */
    const HTTP_No_Content = 204;

    /**
     * @var int The server has fulfilled the request and the user agent SHOULD reset the document view
     * which caused the request to be sent.
     */
    const HTTP_Reset_Content = 205;

    /**
     * @var int The server has fulfilled the partial GET request for the resource.
     */
    const HTTP_Partial_Content = 206;

    /**
     * @var int The response contains a XML document which contains multiple status codes for multiple operations.
     */
    const HTTP_Multi_Status = 207;

    // ----------------------------------
    // 3XX - Redirections
    // ----------------------------------

    /**
     * @var int The requested resource corresponds to any one of a set of representations,
     * each with its own specific location and information so that the user (or user agent) can select a
     * preferred representation and redirect its request to that location..
     */
    const HTTP_Multiple_Choices = 300;

    /**
     * @var int The requested resource has been assigned a new permanent URI and any future references to this
     * resource SHOULD use one of the returned URIs.
     */
    const HTTP_Moved_Permanently = 301;

    /**
     * @var int The requested resource resides temporarily under a different URI.
     */
    const HTTP_Found = 302;

    /**
     * @var int The response to the request can be found under a different URI and SHOULD be retrieved
     * using a GET method on that resource.
     */
    const HTTP_See_Other = 303;

    /**
     * @var int If the client has performed a conditional GET request and access is allowed,
     * but the document has not been modified, the server SHOULD respond with this status code.
     */
    const HTTP_Not_Modified = 304;

    /**
     * @var int The requested resource resides temporarily under a different URI.
     */
    const HTTP_Temporary_Redirect = 307;

    /**
     * @var int The requested resource resides permanently under a different URI.
     */
    const HTTP_Permanent_Redirect = 308;

    // ----------------------------------
    // 4XX - Client errors
    // ----------------------------------

    /**
     * @var int The request could not be understood by the server due to malformed syntax.
     */
    const HTTP_Bad_Request = 400;

    /**
     * @var int The request requires user authentication.
     */
    const HTTP_Unauthorized = 401;

    /**
     * @var int Payment Required. Reserved for future use.
     */
    const HTTP_Payment_Required = 402;

    /**
     * @var int The server understood the request, but is refusing to fulfill it.
     */
    const HTTP_Forbidden = 403;

    /**
     * @var int The server has not found anything matching the Request-URI.
     */
    const HTTP_Not_Found = 404;

    /**
     * @var int The method specified in the Request-Line is not allowed for the resource identified by the Request-URI.
     */
    const HTTP_Method_Not_Allowed = 405;

    /**
     * @var int The resource identified by the request is only capable of generating response entities
     * which have content characteristics not acceptable according to the accept headers sent in the request.
     */
    const HTTP_Not_Acceptable = 406;

    /**
     * @var int The client did not produce a request within the time that the server was prepared to wait.
     */
    const HTTP_Request_Time_Out = 408;

    /**
     * @var int The request could not be completed due to a conflict with the current state of the resource.
     */
    const HTTP_Conflict = 409;

    /**
     * @var int The requested resource is no longer available at the server and no forwarding address is known.
     */
    const HTTP_Gone = 410;

    /**
     * @var int The server refuses to accept the request without a defined Content-Length.
     */
    const HTTP_Length_Required = 411;

    /**
     * @var int The precondition given in one or more of the request-header fields evaluated to false
     * when it was tested on the server.
     */
    const HTTP_Precondition_Failed = 412;

    /**
     * @var int The server is refusing to process a request because the request entity is larger than
     * the server is willing or able to process.
     */
    const HTTP_Request_Entity_Too_Large = 413;

    /**
     * @var int The server is refusing to service the request because the entity of the request is in a format
     * not supported by the requested resource for the requested method.
     */
    const HTTP_Unsupported_Media_Type = 415;

    /**
     * @var int The requested entity is currently locked.
     */
    const HTTP_Locked = 423;

    /**
     * @var int The server is refusing to process a request because it first needs a dependant request to be fulfilled.
     */
    const HTTP_Failed_Dependency = 424;

    /**
     * @var int The client sent too many requests in a timespan.
     */
    const HTTP_Too_Many_Requests = 429;

    /**
     * @var int The server is refusing to service the request because it's content is unavailable for legal resons.
     */
    const HTTP_Unavailable_For_Legal_Reasons = 451;

    // ----------------------------------
    // 5XX - Server errors
    // ----------------------------------

    /**
     * @var int The server encountered an unexpected condition which prevented it from fulfilling the request.
     */
    const HTTP_Internal_Server_Error = 500;

    /**
     * @var int The server does not support the functionality required to fulfill the request.
     */
    const HTTP_Not_Implemented = 501;

    /**
     * @var int The server is currently unable to handle the request due to a temporary overloading or
     * maintenance of the server.
     */
    const HTTP_Service_Unavailable = 503;

    /**
     * @var int The server has not enough storage left to handle or fulfill the request.
     */
    const HTTP_Insufficient_Storage = 507;

}